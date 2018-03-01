<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Validator\IdentityProviders;

use Hal\Core\Entity\System\UserIdentityProvider;
use Hal\Core\Type\IdentityProviderEnum;
use Hal\UI\Validator\ValidatorErrorTrait;
use Hal\UI\Validator\ValidatorTrait;
use Psr\Http\Message\ServerRequestInterface;

class LDAPValidator implements IdentityProviderValidatorInterface
{
    use ValidatorErrorTrait;
    use ValidatorTrait;

    // todo move to UserIdentityProvider?
    public const ATTR_HOST = 'ldap.host';
    public const ATTR_DOMAIN = 'ldap.domain';
    public const ATTR_BASE_DN = 'ldap.base_dn';
    public const ATTR_UNIQUE_ID = 'ldap.attr.unique_id';

    private const REGEX_CHARACTER_CLASS_HOST = '[a-zA-Z0-9]{1}[a-zA-Z0-9\.\-]{3,150}(\:[0-9]{1,5})?';
    private const REGEX_CHARACTER_CLASS_ASCII = '[:ascii:]+';
    private const REGEX_CHARACTER_CLASS_ALPHANUMERIC = '[a-zA-Z0-9]+';

    private const ERR_INVALID_HOSTNAME = 'Please enter a valid server hostname.';
    private const ERR_INVALID_DOMAIN = 'Please enter a valid LDAP domain.';
    private const ERR_INVALID_BASE_DN = 'Please enter a valid Base DN.';
    private const ERR_INVALID_UNIQUE_ID = 'Please enter a valid Unique ID.';

    /**
     * @param array $parameters
     *
     * @return UserIdentityProvider|null
     */
    public function isValid(array $parameters): ?UserIdentityProvider
    {
        $this->resetErrors();

        $hostname = trim($parameters['ldap_host'] ?? '');
        $domain = trim($parameters['ldap_domain'] ?? '');
        $baseDN = trim($parameters['ldap_base_dn'] ?? '');
        $uniqueID = trim($parameters['ldap_unique_id'] ?? '');

        $this->validateHostname($hostname);
        $this->validateDomain($domain);
        $this->validateBaseDN($baseDN);
        $this->validateUniqueID($uniqueID);

        if ($this->hasErrors()) {
            return null;
        }

        // Set null on empty fields so they are removed from the parameters
        $hostname = (strlen($hostname) > 0) ? $hostname : null;
        $domain = (strlen($domain) > 0) ? $domain : null;
        $baseDN = (strlen($baseDN) > 0) ? $baseDN : null;
        $uniqueID = (strlen($uniqueID) > 0) ? $uniqueID : null;

        $provider = (new UserIdentityProvider)
            ->withParameter(self::ATTR_HOST, $hostname)
            ->withParameter(self::ATTR_DOMAIN, $domain)
            ->withParameter(self::ATTR_BASE_DN, $baseDN)
            ->withParameter(self::ATTR_UNIQUE_ID, $uniqueID);

        return $provider;
    }

    /**
     * @param UserIdentityProvider $provider
     * @param array $parameters
     *
     * @return UserIdentityProvider|null
     */
    public function isEditValid(UserIdentityProvider $provider, array $parameters): ?UserIdentityProvider
    {
        $this->resetErrors();

        $hostname = trim($parameters['ldap_host'] ?? '');
        $domain = trim($parameters['ldap_domain'] ?? '');
        $baseDN = trim($parameters['ldap_base_dn'] ?? '');
        $uniqueID = trim($parameters['ldap_unique_id'] ?? '');

        $this->validateHostname($hostname);
        $this->validateDomain($domain);
        $this->validateBaseDN($baseDN);
        $this->validateUniqueID($uniqueID);

        if ($this->hasErrors()) {
            return null;
        }

        // Set null on empty fields so they are removed from the parameters
        $hostname = (strlen($hostname) > 0) ? $hostname : null;
        $domain = (strlen($domain) > 0) ? $domain : null;
        $baseDN = (strlen($baseDN) > 0) ? $baseDN : null;
        $uniqueID = (strlen($uniqueID) > 0) ? $uniqueID : null;

        $provider
            ->withParameter(self::ATTR_HOST, $hostname)
            ->withParameter(self::ATTR_DOMAIN, $domain)
            ->withParameter(self::ATTR_BASE_DN, $baseDN)
            ->withParameter(self::ATTR_UNIQUE_ID, $uniqueID);

        return $provider;
    }

    /**
     * @param ServerRequestInterface $request
     * @param UserIdentityProvider|null $provider
     *
     * @return array
     */
    public function getFormData(ServerRequestInterface $request, ?UserIdentityProvider $provider): array
    {
        $data = $request->getParsedBody();

        $type = IdentityProviderEnum::TYPE_LDAP;

        if ($provider && $request->getMethod() !== 'POST') {
            $data["${type}_host"] = $provider->parameter(self::ATTR_HOST);
            $data["${type}_domain"] = $provider->parameter(self::ATTR_DOMAIN);
            $data["${type}_base_dn"] = $provider->parameter(self::ATTR_BASE_DN);
            $data["${type}_unique_id"] = $provider->parameter(self::ATTR_UNIQUE_ID);
        }

        return [
            "${type}_host" => $data["${type}_host"] ?? '',
            "${type}_domain" => $data["${type}_domain"] ?? '',
            "${type}_base_dn" => $data["${type}_base_dn"] ?? '',
            "${type}_unique_id" => $data["${type}_unique_id"] ?? '',
        ];
    }

    /**
     * @param string $hostname
     *
     * @return void
     */
    private function validateHostname($hostname)
    {
        if (!$this->validateIsRequired($hostname) || !$this->validateSanityCheck($hostname)) {
            $this->addRequiredError('LDAP Host', 'ldap_host');
            return;
        }

        if (!$this->validateCharacterWhitelist($hostname, self::REGEX_CHARACTER_CLASS_HOST)) {
            $this->addError(self::ERR_INVALID_HOSTNAME, 'ldap_host');
        }

        if (!$this->validateLength($hostname, 3, 100)) {
            $this->addLengthError('LDAP Host', 3, 100, 'ldap_host');
        }
    }

    /**
     * @param string $domain
     *
     * @return void
     */
    private function validateDomain($domain)
    {
        // always optional
        if (strlen($domain) === 0) {
            return;
        }

        if (!$this->validateCharacterWhitelist($domain, self::REGEX_CHARACTER_CLASS_ASCII)) {
            $this->addError(self::ERR_INVALID_DOMAIN, 'ldap_domain');
        }

        if (!$this->validateLength($domain, 2, 100)) {
            $this->addLengthError('Domain', 2, 100, 'ldap_domain');
        }
    }

    /**
     * @param string $baseDN
     *
     * @return void
     */
    private function validateBaseDN($baseDN)
    {
        if (!$this->validateCharacterWhitelist($baseDN, self::REGEX_CHARACTER_CLASS_ASCII)) {
            $this->addError(self::ERR_INVALID_BASE_DN, 'ldap_base_dn');
        }

        if (!$this->validateLength($baseDN, 3, 100)) {
            $this->addLengthError('Base DN', 3, 100, 'ldap_base_dn');
        }
    }

    /**
     * @param string $uniqueID
     *
     * @return void
     */
    private function validateUniqueID($uniqueID)
    {
        // always optional
        if (strlen($uniqueID) === 0) {
            return;
        }

        if (!$this->validateCharacterWhitelist($uniqueID, self::REGEX_CHARACTER_CLASS_ALPHANUMERIC)) {
            $this->addError(self::ERR_INVALID_UNIQUE_ID, 'ldap_unique_id');
        }

        if (!$this->validateLength($uniqueID, 3, 100)) {
            $this->addLengthError('Unique ID', 3, 100, 'ldap_unique_id');
        }
    }
}
