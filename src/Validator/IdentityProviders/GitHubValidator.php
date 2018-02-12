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

class GitHubValidator implements IdentityProviderValidatorInterface
{
    use OptionTrait;
    use ValidatorErrorTrait;
    use ValidatorTrait;

    public const ATTR_CLIENT_ID = 'gh.client_id';
    public const ATTR_CLIENT_SECRET = 'gh.client_secret';
    public const ATTR_API_DOMAIN = 'gh.api_domain';

    private const REGEX_CHARACTER_CLASS_API_DOMAIN = '[Hh][Tt]{2}[Pp]([Ss])?\:\/\/[a-zA-Z0-9]{1}[a-zA-Z0-9\.\-]{3,150}(\:[0-9]{1,5})?';

    private const ERR_INVALID_API_DOMAIN = 'Please enter a valid API Domain.';

    /**
     * @param array $parameters
     *
     * @return UserIdentityProvider|null
     */
    public function isValid(array $parameters): ?UserIdentityProvider
    {
        $this->resetErrors();

        $clientID = trim($parameters['gh_client_id'] ?? '');
        $clientSecret = trim($parameters['gh_client_secret'] ?? '');
        $apiDomain = rtrim(trim($parameters['gh_api_domain'] ?? ''), '/');

        $this->validateClientID($clientID);
        $this->validateClientSecret($clientSecret);
        $this->validateDomain($apiDomain);

        if ($this->hasErrors()) {
            return null;
        }

        $provider = (new UserIdentityProvider)
            ->withParameter(self::ATTR_CLIENT_ID, $clientID)
            ->withParameter(self::ATTR_CLIENT_SECRET, $clientSecret)
            ->withParameter(self::ATTR_API_DOMAIN, $apiDomain)
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

        $clientID = trim($parameters['gh_client_id'] ?? '');
        $clientSecret = trim($parameters['gh_client_secret'] ?? '');
        $apiDomain = rtrim(trim($parameters['gh_api_domain'] ?? ''), '/');

        $this->validateClientID($clientID);
        $this->validateClientSecret($clientSecret);
        $this->validateDomain($apiDomain);

        if ($this->hasErrors()) {
            return null;
        }

        $provider
            ->withParameter(self::ATTR_CLIENT_ID, $clientID)
            ->withParameter(self::ATTR_CLIENT_SECRET, $clientSecret)
            ->withParameter(self::ATTR_API_DOMAIN, $apiDomain);

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
            $data['gh_client_id'] = $provider->parameter(self::ATTR_CLIENT_ID);
            $data['gh_client_secret'] = $provider->parameter(self::ATTR_CLIENT_SECRET);
            $data['gh_api_domain'] = $provider->parameter(self::ATTR_API_DOMAIN);
        }

        return [
            'gh_client_id' => $data['gh_client_id'],
            'gh_client_secret' => $data['gh_client_secret'],
            'gh_api_domain' => $data['gh_api_domain']
        ];
    }

    private function validateClientID($clientID)
    {
        if (!$this->validateIsRequired($clientID) || !$this->validateSanityCheck($clientID)) {
            $this->addRequiredError('Client ID', 'gh_client_id');
            return;
        }
    }

    private function validateClientSecret($clientSecret)
    {
        if (!$this->validateIsRequired($clientSecret) || !$this->validateSanityCheck($clientSecret)) {
            $this->addRequiredError('Client Secret', 'gh_client_secret');
            return;
        }
    }

    private function validateDomain($apiDomain)
    {
        if (!$this->validateIsRequired($apiDomain) || !$this->validateSanityCheck($apiDomain)) {
            $this->addRequiredError('API Domain', 'gh_api_domain');
            return;
        }

        if (!$this->validateCharacterWhitelist($apiDomain, self::REGEX_CHARACTER_CLASS_API_DOMAIN)) {
            $this->addError(self::ERR_INVALID_API_DOMAIN, 'gh_api_domain');
        }

        if (!$this->validateLength($apiDomain, 8, 100)) {
            $this->addLengthError('API Domain', 8, 100, 'gh_api_domain');
        }
    }
}
