<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Validator\Targets;

use Hal\Core\Entity\Target;
use Hal\Core\Type\TargetEnum;
use Hal\UI\Utility\OptionTrait;
use Hal\UI\Validator\ValidatorErrorTrait;
use Hal\UI\Validator\ValidatorTrait;
use Psr\Http\Message\ServerRequestInterface;

class RSyncValidator implements TargetValidatorInterface
{
    use OptionTrait;
    use ValidatorErrorTrait;
    use ValidatorTrait;

    public const ALLOW_OPTIONAL = 1;

    private const REGEX_CHARACTER_STARTING_SLASH = '#^/#';
    private const REGEX_CHARACTER_STRICT_WHITESPACE = '\f\n\r\t\v ';
    private const REGEX_CHARACTER_CLASS_HOST = '[a-zA-Z0-9]{1}[a-zA-Z0-9\.\-]{3,150}(\:[0-9]{1,5})?';

    private const ERR_PATH_CHARACTERS_STARTING_SLASH = 'File path must begin with a forward slash (/)';
    private const ERT_CHARACTERS_STRICT_WHITESPACE = '%s must not contain any whitespace.';
    private const ERR_INVALID_SERVERS = 'Please enter at least one valid server hostname.';

    /**
     * @inheritDoc
     */
    public function isValid(array $parameters): ?Target
    {
        $this->resetErrors();

        $path = trim($parameters['rsync_path'] ?? '');
        $servers = $parameters['rsync_servers'] ?? '';

        if (!$this->allowOptional($path)) {
            $this->validatePath($path);
        }

        if (!$this->allowOptional($servers)) {
            $this->validateServers($servers);
        }

        if ($this->hasErrors()) {
            return null;
        }

        // Set null on empty fields so they are removed from the parameters
        $path = (strlen($path) > 0) ? $path : null;
        $servers = (strlen($servers) > 0) ? $servers : null;

        $target = (new Target)
            ->withParameter(Target::PARAM_REMOTE_PATH, $path)
            ->withParameter('servers', $servers);

        return $target;
    }

    /**
     * @inheritDoc
     */
    public function isEditValid(Target $target, array $parameters): ?Target
    {
        $this->resetErrors();

        $path = trim($parameters['rsync_path'] ?? '');
        $servers = $parameters['rsync_servers'] ?? '';

        if (!$this->allowOptional($path)) {
            $this->validatePath($path);
        }

        if (!$this->allowOptional($servers)) {
            $this->validateServers($servers);
        }

        if ($this->hasErrors()) {
            return null;
        }

        // Set null on empty fields so they are removed from the parameters
        $path = (strlen($path) > 0) ? $path : null;
        $servers = (strlen($servers) > 0) ? $servers : null;

        $target
            ->withParameter(Target::PARAM_REMOTE_PATH, $path)
            ->withParameter('servers', $servers);

        return $target;
    }

    /**
     * @inheritDoc
     */
    public function getFormData(ServerRequestInterface $request, ?Target $target): array
    {
        $data = $request->getParsedBody();

        $type = TargetEnum::TYPE_RSYNC;

        if ($target && $request->getMethod() !== 'POST') {
            $data["${type}_path"] = $target->parameter(Target::PARAM_REMOTE_PATH);
            $data["${type}_servers"] = $target->parameter('servers');
        }

        return [
            "${type}_path" => $data["${type}_path"] ?? '',
            "${type}_servers" => $data["${type}_servers"] ?? '',
        ];
    }

    /**
     * @param string $value
     *
     * @param bool
     */
    private function allowOptional($value)
    {
        if (strlen($value) > 0) {
            return false;
        }

        return $this->isFlagEnabled(self::ALLOW_OPTIONAL);
    }

    /**
     * @param string $path
     *
     * @return void
     */
    private function validatePath($path)
    {
        if (!$this->validateIsRequired($path) || !$this->validateSanityCheck($path)) {
            $this->addRequiredError('Path', 'rsync_path');
            return;
        }

        if (!$this->validateRegex($path, self::REGEX_CHARACTER_STARTING_SLASH)) {
            $this->addError(self::ERR_PATH_CHARACTERS_STARTING_SLASH, 'rsync_path');
        }

        if (!$this->validateCharacterBlacklist($path, self::REGEX_CHARACTER_STRICT_WHITESPACE)) {
            $error = sprintf(self::ERT_CHARACTERS_STRICT_WHITESPACE, 'Path');
            $this->addError($error, 'rsync_path');
        }

        if (!$this->validateLength($path, 1, 200)) {
            $this->addLengthError('Path', 1, 200, 'rsync_path');
        }
    }

    /**
     * @param string $servers
     *
     * @return void
     */
    private function validateServers($servers)
    {
        if (!$this->validateIsRequired($servers) || !$this->validateSanityCheck($servers)) {
            $this->addRequiredError('Servers', 'rsync_servers');
            return;
        }

        if (!$this->validateCharacterWhitelist($servers, self::REGEX_CHARACTER_CLASS_HOST)) {
            $this->addError(self::ERR_INVALID_SERVERS, 'rsync_servers');
        }

        if (!$this->validateLength($servers, 3, 200)) {
            $this->addLengthError('Servers', 3, 200, 'rsync_servers');
        }
    }
}
