<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Validator\IdentityProviders;

use Hal\Core\Entity\System\UserIdentityProvider;
use Hal\Core\Type\IdentityProviderEnum;
use Hal\UI\Utility\OptionTrait;
use Hal\UI\Validator\ValidatorErrorTrait;
use Hal\UI\Validator\ValidatorTrait;
use Psr\Http\Message\ServerRequestInterface;

class GitHubEnterpriseValidator implements IdentityProviderValidatorInterface
{
    use OptionTrait;
    use ValidatorErrorTrait;
    use ValidatorTrait;

    public const ATTR_CLIENT_ID = 'ghe.client_id';
    public const ATTR_CLIENT_SECRET = 'ghe.client_secret';
    public const ATTR_DOMAIN = 'ghe.domain';

    private const REGEX_CHARACTER_CLASS_DOMAIN = '[Hh][Tt]{2}[Pp]([Ss])?\:\/\/[a-zA-Z0-9]{1}[a-zA-Z0-9\.\-]{2,150}(\:[0-9]{1,5})?';

    private const ERR_INVALID_DOMAIN = 'Please enter a valid Github domain.';

    /**
     * @param array $parameters
     *
     * @return UserIdentityProvider|null
     */
    public function isValid(array $parameters): ?UserIdentityProvider
    {
        $this->resetErrors();

        $clientID = trim($parameters['ghe_client_id'] ?? '');
        $clientSecret = trim($parameters['ghe_client_secret'] ?? '');
        $domain = rtrim(trim($parameters['ghe_domain'] ?? ''), '/');

        $this->validateClientID($clientID);
        $this->validateClientSecret($clientSecret);
        $this->validateDomain($domain);

        if ($this->hasErrors()) {
            return null;
        }

        $provider = (new UserIdentityProvider)
            ->withParameter(self::ATTR_CLIENT_ID, $clientID)
            ->withParameter(self::ATTR_CLIENT_SECRET, $clientSecret)
            ->withParameter(self::ATTR_DOMAIN, $domain)
            ->withIsOAuth(true);

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

        $clientID = trim($parameters['ghe_client_id'] ?? '');
        $clientSecret = trim($parameters['ghe_client_secret'] ?? '');
        $domain = rtrim(trim($parameters['ghe_domain'] ?? ''), '/');

        $this->validateClientID($clientID);
        $this->validateClientSecret($clientSecret);
        $this->validateDomain($domain);

        if ($this->hasErrors()) {
            return null;
        }

        $provider
            ->withParameter(self::ATTR_CLIENT_ID, $clientID)
            ->withParameter(self::ATTR_CLIENT_SECRET, $clientSecret)
            ->withParameter(self::ATTR_DOMAIN, $domain);

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

        if ($provider && $request->getMethod() !== 'POST') {
            $data['ghe_client_id'] = $provider->parameter(self::ATTR_CLIENT_ID);
            $data['ghe_client_secret'] = $provider->parameter(self::ATTR_CLIENT_SECRET);
            $data['ghe_domain'] = $provider->parameter(self::ATTR_DOMAIN);
        }

        return [
            'ghe_client_id' => $data['ghe_client_id'],
            'ghe_client_secret' => $data['ghe_client_secret'],
            'ghe_domain' => $data['ghe_domain']
        ];
    }

    /**
     * @param string $clientID
     *
     * @return void
     */
    private function validateClientID($clientID)
    {
        if (!$this->validateIsRequired($clientID) || !$this->validateSanityCheck($clientID)) {
            $this->addRequiredError('Client ID', 'ghe_client_id');
            return;
        }
    }

    /**
     * @param string $clientSecret
     *
     * @return void
     */
    private function validateClientSecret($clientSecret)
    {
        if (!$this->validateIsRequired($clientSecret) || !$this->validateSanityCheck($clientSecret)) {
            $this->addRequiredError('Client Secret', 'ghe_client_secret');
            return;
        }
    }

    /**
     * @param string $domain
     *
     * @return void
     */
    private function validateDomain($domain)
    {
        if (!$this->validateIsRequired($domain) || !$this->validateSanityCheck($domain)) {
            $this->addRequiredError('Domain', 'ghe_domain');
            return;
        }

        if (!$this->validateCharacterWhitelist($domain, self::REGEX_CHARACTER_CLASS_DOMAIN)) {
            $this->addError(self::ERR_INVALID_DOMAIN, 'ghe_domain');
        }

        if (!$this->validateLength($domain, 8, 100)) {
            $this->addLengthError('Domain', 8, 100, 'ghe_domain');
        }
    }
}
