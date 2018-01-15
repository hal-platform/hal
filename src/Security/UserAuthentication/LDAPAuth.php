<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Security\UserAuthentication;

use Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\User;
use Hal\Core\Entity\System\UserIdentityProvider;
use Hal\Core\Type\IdentityProviderEnum;
use Hal\UI\Utility\OptionTrait;
use Hal\UI\Validator\IdentityProviders\LDAPValidator;
use Hal\UI\Validator\ValidatorErrorTrait;
use Symfony\Component\Ldap\Adapter\AdapterInterface;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Ldap\LdapInterface;
use Symfony\Component\Ldap\Exception\LdapException;

class LDAPAuth
{
    use OptionTrait;
    use ValidatorErrorTrait;

// uid={0}
    const USER_ID = 'objectGUID';

    public const AUTO_CREATE_USER = 1;
    public const DEFAULT_FLAGS = self::AUTO_CREATE_USER;

    public const DEFAULT_LDAP_PORT = 389;

    private const LDAP_ADAPTER = 'ext_ldap';

    private const ERR_USER_NOT_FOUND = 'Invalid sign-in information. Please try again.';
    private const ERR_IDP_MISCONFIGURED = 'LDAP Identity Provider is misconfigured.';


    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var EntityRepository
     */
    private $userRepo;

    /**
     * @var array
     */
    private $queryRestriction;

    /**
     * @var string
     */
    private $defaultUsernameAttribute;

    /**
     * @param EntityManagerInterface $em
     * @param string $queryRestriction
     * @param string $defaultUsernameAttribute
     */
    public function __construct(EntityManagerInterface $em, array $queryRestriction, string $defaultUsernameAttribute)
    {
        $this->em = $em;
        $this->userRepo = $em->getRepository(User::class);

        $this->queryRestriction = $queryRestriction;
        $this->defaultUsernameAttribute = $defaultUsernameAttribute;

        $this->withFlag(self::DEFAULT_FLAGS);
    }

    /**
     * @param UserIdentityProvider $idp
     * @param string $username
     * @param string $password
     *
     * @return User|null
     */
    public function authenticate(UserIdentityProvider $idp, string $username, string $password): ?User
    {
        if (strlen($username) === 0 || strlen($password) === 0) {
            $this->addError(self::ERR_USER_NOT_FOUND);
            return null;
        }

        $ldap = $this->getProviderClient($idp);
        if (!$ldap instanceof LdapInterface) {
            return null;
        }

        $data = $this->retrieveUser($idp, $ldap, $username, $password);

        if (!$data) {
            $this->addError(self::ERR_USER_NOT_FOUND);
            return null;
        }

        $user = $this->userRepo->findOneBy([
            'provider' => $idp,
            'providerUniqueID' => $data['id']
        ]);

        if ($user instanceof User) {
            return $user;
        }

        if ($this->isFlagEnabled(self::AUTO_CREATE_USER)) {
            return $this->autoCreateUser($idp, $data);
        }

        $this->addError(self::ERR_USER_NOT_FOUND);
        return null;
    }

    /**
     * @param UserIdentityProvider $idp
     * @param LdapInterface $ldap
     * @param string $username
     * @param string $password
     *
     * @return array|null
     */
    private function retrieveUser(UserIdentityProvider $idp, LdapInterface $ldap, $username, $password)
    {
        $fqUsername = $username;
        if ($domain = $idp->parameter(LDAPValidator::ATTR_DOMAIN)) {
            $fqUsername = $domain . '\\' . $fqUsername;
        }

        try {
            $ldap->bind($fqUsername, $password);

        } catch (Exception $ex) {
            // Symfony suppresses errors, but our error handler does not properly ignore suppressed errors.
            $this->addError(self::ERR_USER_NOT_FOUND);
            return null;
        }

        $dn = $idp->parameter(LDAPValidator::ATTR_BASE_DN);
        $attribute = $idp->parameter(LDAPValidator::ATTR_UNIQUE_ID) ?? $this->defaultUsernameAttribute;

        // @todo - not sure if DN is required
        // @todo - not sure if DN is required
        // @todo - not sure if DN is required
        // @todo - not sure if DN is required
        // @todo - not sure if DN is required
        // @todo - not sure if DN is required
        if (strlen($dn) === 0 || strlen($attribute) === 0) {
            $this->addError(self::ERR_IDP_MISCONFIGURED);
            return null;
        }

        $baseDN = $ldap->escape($dn);
        $query = $this->buildLDAPQuery($ldap, $attribute, $username);
        $attributes = [
            'id' => self::USER_ID,
            'username' => $attribute
        ];

        $data = $this->getUserData($ldap, $baseDN, $query, $attributes);

        return $data;
    }

    /**
     * @param LdapInterface $ldap
     * @param string $baseDN
     * @param string $query
     * @param array $attributes
     *
     * @return array|null
     */
    private function getUserData(LdapInterface $ldap, $baseDN, $query, array $attributes)
    {
        $query = $ldap->query($baseDN, $query, [
            'filter' => array_values($attributes)
        ]);

        $results = $query->execute();

        if (count($results) !== 1) {
            return null;
        }

        $data = array_pop($results);

        $user = [];
        foreach (array_keys($attributes) as $attr => $adAttr) {
            $value = $data->getAttribute($adAttr)[0];
            if (strlen($value) === 0) {
                return null;
            }

            if ($adAttr === self::USER_ID) {
                $value = unpack("H*hex", $value)['hex'];
            }

            $user[$attr] = $value;
        }

        return $user;
    }

    /**
     * @param UserIdentityProvider $idp
     * @param array $data
     *
     * @return User
     */
    private function autoCreateUser(UserIdentityProvider $idp, array $data): User
    {
        $user = (new User)
            ->withName($data['username'])
            ->withParameter('ldap.id', $data['id'])
            ->withParameter('ldap.username', $data['username'])
            ->withProviderUniqueID($data['id'])
            ->withProvider($idp);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    /**
     * @param UserIdentityProvider $idp
     *
     * @return LdapInterface|null
     */
    private function getProviderClient(UserIdentityProvider $idp): ?LdapInterface
    {
        if ($idp->type() !== IdentityProviderEnum::TYPE_LDAP) {
            $this->addError(self::ERR_IDP_MISCONFIGURED);
            return null;
        }

        $host = $idp->parameter(LDAPValidator::ATTR_HOST);

        $hostname = strstr($host, ':', true);
        $port = strstr($host, ':', false);

        $hostname = ($hostname !== false) ? $hostname : $host;
        $port = ($port !== false) ? $port : self::DEFAULT_LDAP_PORT;

        if (strlen($host) === 0 || strlen($port) === 0) {
            $this->addError(self::ERR_IDP_MISCONFIGURED);
            return null;
        }

        $ldap = $this->LDAPClientFactory($host, $port);
        if (!$ldap instanceof AdapterInterface) {
            $this->addError(self::ERR_IDP_MISCONFIGURED);
            return null;
        }

        return $ldap;
    }

    /**
     * @param LdapInterface $ldap
     * @param string $attribute
     * @param string $username
     *
     * @return string
     */
    private function buildLDAPQuery(LdapInterface $ldap, $attribute, $username)
    {
        $query = [];
        foreach ($this->queryRestriction as $attr => $value) {
            $query[] = sprintf('(%s=%s)', $attr, $ldap->escape($value));
        }

        $query[] = sprintf('(%s=%s)', $ldap->escape($attribute), $ldap->escape($username));

        return sprintf('(&%s)', implode('', $query));
    }

    /**
     * @param string $host
     * @param string $port
     *
     * @return LdapInterface|null
     */
    protected function LDAPClientFactory($host, $port): ?LdapInterface
    {
        $options = [
            'host' => $host,
            'port' => $port
        ];

        try {
            $ldap = Ldap::create(self::LDAP_ADAPTER, $options);
        } catch (LdapException $ex) {
            return null;
        }

        return $ldap;
    }
}
