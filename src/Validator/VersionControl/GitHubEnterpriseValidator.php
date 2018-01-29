<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Validator\VersionControl;

use Hal\Core\Entity\System\VersionControlProvider;
use Hal\Core\Type\VCSProviderEnum;
use Hal\UI\Utility\OptionTrait;
use Hal\UI\Validator\ValidatorErrorTrait;
use Hal\UI\Validator\ValidatorTrait;
use Psr\Http\Message\ServerRequestInterface;

class GitHubEnterpriseValidator implements VersionControlValidatorInterface
{
    use OptionTrait;
    use ValidatorErrorTrait;
    use ValidatorTrait;

    private const REGEX_CHARACTER_STRICT_WHITESPACE = '\f\n\r\t\v ';
    private const REGEX_URL = '@^https?\:\/\/[[:ascii:]]+$@';

    private const ERT_CHARACTERS_STRICT_WHITESPACE = '%s must not contain any whitespace.';

    private const ERR_INVALID_URL = 'Base URL must be a URL including http:// or https://';

    private const ATTR_URL = 'ghe.url';
    private const ATTR_TOKEN = 'ghe.token';

    /**
     * @inheritDoc
     */
    public function isValid(array $parameters): ?VersionControlProvider
    {
        $this->resetErrors();

        $baseURL = trim($parameters['ghe_url'] ?? '');
        $token = trim($parameters['ghe_token'] ?? '');

        $this->validateURL($baseURL);
        $this->validateToken($token);

        if ($this->hasErrors()) {
            return null;
        }

        // Set null on empty fields so they are removed from the parameters
        $baseURL = (strlen($baseURL) > 0) ? $baseURL : null;
        $token = (strlen($token) > 0) ? $token : null;

        $provider = (new VersionControlProvider)
            ->withParameter(self::ATTR_URL, $baseURL)
            ->withParameter(self::ATTR_TOKEN, $token);

        return $provider;
    }

    /**
     * @inheritDoc
     */
    public function isEditValid(VersionControlProvider $provider, array $parameters): ?VersionControlProvider
    {
        $this->resetErrors();

        $baseURL = trim($parameters['ghe_url'] ?? '');
        $token = trim($parameters['ghe_token'] ?? '');

        $this->validateURL($baseURL);
        $this->validateToken($token);

        if ($this->hasErrors()) {
            return null;
        }

        // Set null on empty fields so they are removed from the parameters
        $baseURL = (strlen($baseURL) > 0) ? $baseURL : null;
        $token = (strlen($token) > 0) ? $token : null;

        $provider
            ->withParameter(self::ATTR_URL, $baseURL)
            ->withParameter(self::ATTR_TOKEN, $token);

        return $provider;
    }

    /**
     * @inheritDoc
     */
    public function getFormData(ServerRequestInterface $request, ?VersionControlProvider $provider): array
    {
        $data = $request->getParsedBody();

        $type = VCSProviderEnum::TYPE_GITHUB_ENTERPRISE;

        if ($provider && $request->getMethod() !== 'POST') {
            $data["${type}_url"] = $provider->parameter(self::ATTR_URL);
            $data["${type}_token"] = $provider->parameter(self::ATTR_TOKEN);
        }

        return [
            "${type}_url" => $data["${type}_url"] ?? '',
            "${type}_token" => $data["${type}_token"] ?? '',
        ];
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

        if (!$this->validateLength($url, 3, 100)) {
            $this->addLengthError('GitHub Base URL', 3, 100, 'ghe_url');
        }

        if (!$this->validateRegex($url, self::REGEX_URL)) {
            $this->addError(self::ERR_INVALID_URL, 'ghe_url');
        }
    }

    /**
     * @param string $token
     *
     * @return void
     */
    private function validateToken($token)
    {
        if (!$this->validateIsRequired($token) || !$this->validateSanityCheck($token)) {
            $this->addRequiredError('GitHub Token', 'ghe_token');
            return;
        }

        if (!$this->validateLength($token, 3, 100)) {
            $this->addLengthError('GitHub Token', 3, 100, 'ghe_token');
        }

        if (!$this->validateCharacterBlacklist($token, self::REGEX_CHARACTER_STRICT_WHITESPACE)) {
            $error = sprintf(self::ERT_CHARACTERS_STRICT_WHITESPACE, 'GitHub Token');
            $this->addError($error, 'ghe_token');
        }
    }
}
