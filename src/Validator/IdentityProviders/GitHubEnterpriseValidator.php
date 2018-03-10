<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Validator\IdentityProviders;

use Hal\Core\Entity\System\UserIdentityProvider;
use Hal\Core\Parameters;
use Hal\Core\Type\IdentityProviderEnum;
use Hal\UI\Validator\ValidatorErrorTrait;
use Hal\UI\Validator\ValidatorTrait;
use Psr\Http\Message\ServerRequestInterface;

class GitHubEnterpriseValidator implements IdentityProviderValidatorInterface
{
    use ValidatorErrorTrait;
    use ValidatorTrait;

    private const REGEX_CHARACTER_STRICT_WHITESPACE = '\f\n\r\t\v ';
    private const REGEX_URL = '@^https?\:\/\/[[:ascii:]]+$@';

    private const ERT_CHARACTERS_STRICT_WHITESPACE = '%s must not contain any whitespace.';

    private const ERR_INVALID_URL = 'Base URL must be a URL including http:// or https://';

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
        $baseURL = rtrim(trim($parameters['ghe_url'] ?? ''), '/');

        $this->validateClientID($clientID);
        $this->validateClientSecret($clientSecret);
        $this->validateURL($baseURL);

        if ($this->hasErrors()) {
            return null;
        }

        // Set null on empty fields so they are removed from the parameters
        $clientID = (strlen($clientID) > 0) ? $clientID : null;
        $clientSecret = (strlen($clientSecret) > 0) ? $clientSecret : null;
        $baseURL = (strlen($baseURL) > 0) ? $baseURL : null;

        $provider = (new UserIdentityProvider)
            ->withParameter(Parameters::IDP_GHE_CLIENT_ID, $clientID)
            ->withParameter(Parameters::IDP_GHE_CLIENT_SECRET, $clientSecret)
            ->withParameter(Parameters::IDP_GHE_URL, $baseURL);

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
        $baseURL = rtrim(trim($parameters['ghe_url'] ?? ''), '/');

        $this->validateClientID($clientID);
        $this->validateClientSecret($clientSecret);
        $this->validateURL($baseURL);

        if ($this->hasErrors()) {
            return null;
        }

        // Set null on empty fields so they are removed from the parameters
        $clientID = (strlen($clientID) > 0) ? $clientID : null;
        $clientSecret = (strlen($clientSecret) > 0) ? $clientSecret : null;
        $baseURL = (strlen($baseURL) > 0) ? $baseURL : null;

        $provider
            ->withParameter(Parameters::IDP_GHE_CLIENT_ID, $clientID)
            ->withParameter(Parameters::IDP_GHE_CLIENT_SECRET, $clientSecret)
            ->withParameter(Parameters::IDP_GHE_URL, $baseURL);

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

        $type = IdentityProviderEnum::TYPE_GITHUB_ENTERPRISE;

        if ($provider && $request->getMethod() !== 'POST') {
            $data["${type}_client_id"] = $provider->parameter(Parameters::IDP_GHE_CLIENT_ID);
            $data["${type}_client_secret"] = $provider->parameter(Parameters::IDP_GHE_CLIENT_SECRET);
            $data["${type}_url"] = $provider->parameter(Parameters::IDP_GHE_URL);
        }

        return [
            "${type}_client_id" => $data["${type}_client_id"] ?? '',
            "${type}_client_secret" => $data["${type}_client_secret"] ?? '',
            "${type}_url" => $data["${type}_url"] ?? ''
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

        if (!$this->validateLength($clientID, 3, 100)) {
            $this->addLengthError('Client ID', 3, 100, 'ghe_client_id');
        }

        if (!$this->validateCharacterBlacklist($clientID, self::REGEX_CHARACTER_STRICT_WHITESPACE)) {
            $error = sprintf(self::ERT_CHARACTERS_STRICT_WHITESPACE, 'Client ID');
            $this->addError($error, 'ghe_client_id');
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

        if (!$this->validateLength($clientSecret, 3, 100)) {
            $this->addLengthError('Client Secret', 3, 100, 'ghe_client_secret');
        }

        if (!$this->validateCharacterBlacklist($clientSecret, self::REGEX_CHARACTER_STRICT_WHITESPACE)) {
            $error = sprintf(self::ERT_CHARACTERS_STRICT_WHITESPACE, 'Client Secret');
            $this->addError($error, 'ghe_client_secret');
        }
    }

    /**
     * @param string $url
     *
     * @return void
     */
    private function validateURL($url)
    {
        if (!$this->validateIsRequired($url) || !$this->validateSanityCheck($url)) {
            $this->addRequiredError('GitHub Base URL', 'ghe_url');
            return;
        }

        if (!$this->validateLength($url, 8, 100)) {
            $this->addLengthError('GitHub Base URL', 8, 100, 'ghe_url');
        }

        if (!$this->validateRegex($url, self::REGEX_URL)) {
            $this->addError(self::ERR_INVALID_URL, 'ghe_url');
        }
    }
}
