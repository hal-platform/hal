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
use Hal\UI\Validator\ValidatorErrorTrait;
use Symfony\Component\Ldap\Adapter\AdapterInterface;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Ldap\LdapInterface;
use Symfony\Component\Ldap\Exception\LdapException;

class LDAPAuth
{
    use OptionTrait;
    use ValidatorErrorTrait;

    // todo move to UserIdentityProvider?
    const PARAM_HOST = 'ldap.host';
    const PARAM_PORT = 'ldap.port';
    const PARAM_DOMAIN = 'ldap.domain';
    const PARAM_BASE_DN = 'ldap.base_dn';
    const PARAM_ATTR_OBJECT = 'ldap.attr.object';
    const PARAM_ATTR_USERNAME = 'ldap.attr.username';

    const LDAP_ADAPTER = 'ext_ldap';

    const ERR_USER_NOT_FOUND = 'Invalid sign-in information. Please try again.';
    const ERR_IDP_MISCONFIGURED = 'LDAP Identity Provider is misconfigured.';

    const AUTO_CREATE_USER = 1;
    const DEFAULT_FLAGS = self::AUTO_CREATE_USER;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var EntityRepository
     */
    private $userRepo;

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->userRepo = $em->getRepository(User::class);

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
        if ($domain = $idp->parameter(self::PARAM_DOMAIN)) {
            $fqUsername = $domain . '\\' . $fqUsername;
        }

        try {
            $ldap->bind($fqUsername, $password);

        } catch (Exception $ex) {
            // Symfony suppresses errors, but our error handler does not properly ignore suppressed errors.
            $this->addError(self::ERR_USER_NOT_FOUND);
            return null;
        }

        $dn = $idp->parameter(self::PARAM_BASE_DN);
        $object = $idp->parameter(self::PARAM_ATTR_OBJECT);
        $attribute = $idp->parameter(self::PARAM_ATTR_USERNAME);

        if (strlen($dn) === 0 || strlen($object) === 0 || strlen($attribute) === 0) {
            $this->addError(self::ERR_IDP_MISCONFIGURED);
            return null;
        }

        $baseDN = $ldap->escape($dn);
        $userObject = $ldap->escape($object);
        $usernameAttribute = $ldap->escape($attribute);

        $query = sprintf('(&(objectclass=%s)(%s=%s))', $userObject, $usernameAttribute, $username);
        $attributes = [
            'id' => 'objectGUID',
            'username' => $usernameAttribute
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

            if ($adAttr === 'objectGUID') {
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

        $host = $idp->parameter(self::PARAM_HOST);
        $port = $idp->parameter(self::PARAM_PORT);

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
