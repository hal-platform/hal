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

class GitHubValidator implements IdentityProviderValidatorInterface
{
    use ValidatorErrorTrait;
    use ValidatorTrait;

    private const REGEX_CHARACTER_STRICT_WHITESPACE = '\f\n\r\t\v ';

    private const ERT_CHARACTERS_STRICT_WHITESPACE = '%s must not contain any whitespace.';

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

        $this->validateClientID($clientID);
        $this->validateClientSecret($clientSecret);

        if ($this->hasErrors()) {
            return null;
        }

        // Set null on empty fields so they are removed from the parameters
        $clientID = (strlen($clientID) > 0) ? $clientID : null;
        $clientSecret = (strlen($clientSecret) > 0) ? $clientSecret : null;

        $provider = (new UserIdentityProvider)
            ->withParameter(Parameters::IDP_GH_CLIENT_ID, $clientID)
            ->withParameter(Parameters::IDP_GH_CLIENT_SECRET, $clientSecret);

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

        $this->validateClientID($clientID);
        $this->validateClientSecret($clientSecret);

        if ($this->hasErrors()) {
            return null;
        }

        // Set null on empty fields so they are removed from the parameters
        $clientID = (strlen($clientID) > 0) ? $clientID : null;
        $clientSecret = (strlen($clientSecret) > 0) ? $clientSecret : null;

        $provider
            ->withParameter(Parameters::IDP_GH_CLIENT_ID, $clientID)
            ->withParameter(Parameters::IDP_GH_CLIENT_SECRET, $clientSecret);

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

        $type = IdentityProviderEnum::TYPE_GITHUB;

        if ($provider && $request->getMethod() !== 'POST') {
            $data["${type}_client_id"] = $provider->parameter(Parameters::IDP_GH_CLIENT_ID);
            $data["${type}_client_secret"] = $provider->parameter(Parameters::IDP_GH_CLIENT_SECRET);
        }

        return [
            "${type}_client_id" => $data["${type}_client_id"] ?? '',
            "${type}_client_secret" => $data["${type}_client_secret"] ?? '',
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
            $this->addRequiredError('Client ID', 'gh_client_id');
            return;
        }

        if (!$this->validateLength($clientID, 3, 100)) {
            $this->addLengthError('Client ID', 3, 100, 'gh_client_id');
        }

        if (!$this->validateCharacterBlacklist($clientID, self::REGEX_CHARACTER_STRICT_WHITESPACE)) {
            $error = sprintf(self::ERT_CHARACTERS_STRICT_WHITESPACE, 'Client ID');
            $this->addError($error, 'gh_client_id');
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
            $this->addRequiredError('Client Secret', 'gh_client_secret');
            return;
        }

        if (!$this->validateLength($clientSecret, 3, 100)) {
            $this->addLengthError('Client Secret', 3, 100, 'gh_client_secret');
        }

        if (!$this->validateCharacterBlacklist($clientSecret, self::REGEX_CHARACTER_STRICT_WHITESPACE)) {
            $error = sprintf(self::ERT_CHARACTERS_STRICT_WHITESPACE, 'Client Secret');
            $this->addError($error, 'gh_client_secret');
        }
    }
}
