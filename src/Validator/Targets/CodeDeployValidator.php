<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Validator\Targets;

use Hal\Core\AWS\AWSAuthenticator;
use Hal\Core\Entity\Target;
use Hal\Core\Type\TargetEnum;
use Hal\UI\Validator\ValidatorErrorTrait;
use Hal\UI\Validator\ValidatorTrait;
use Psr\Http\Message\ServerRequestInterface;

class CodeDeployValidator implements TargetValidatorInterface
{
    use ValidatorErrorTrait;
    use ValidatorTrait;

    public const ALLOW_OPTIONAL = 1;

    private const REGEX_CHARACTER_RELAXED_WHITESPACE = '\f\n\r\t\v';

    private const ERR_INVALID_REGION = 'Please select an AWS region.';
    private const ERT_CHARACTERS_RELAXED_WHITESPACE = '%s must not contain tabs or newlines';

    /**
     * @var S3Validator
     */
    private $s3Validator;

    /**
     * @var int
     */
    private $options;

    /**
     * @param S3Validator $s3validator
     * @param int $options
     */
    public function __construct(S3Validator $s3validator, int $options = 0)
    {
        $this->s3Validator = $s3validator;
        $this->options = $options;
    }

    /**
     * @inheritDoc
     */
    public function isValid(array $parameters): ?Target
    {
        $this->resetErrors();

        $region = $parameters['aws_region'] ?? '';

        $name = trim($parameters['cd_name'] ?? '');
        $group = trim($parameters['cd_environment'] ?? '');
        $config = trim($parameters['cd_config'] ?? '');

        $s3Parameters = [
            'aws_region' => $region,
            's3_method' => 'artifact',
            's3_local_path' => '.',
            's3_bucket' => $parameters['s3_bucket'] ?? '',
            's3_remote_path' => $parameters['s3_remote_path'] ?? '',
        ];

        if (!$this->allowOptional($region)) {
            $this->validateRegion($region);
        }

        if ($this->hasErrors()) {
            return null;
        }

        $target = $this->s3Validator->isValid($s3Parameters);
        if (!$target) {
            $this->importErrors($this->s3Validator->errors());
            return null;
        }

        if (!$this->allowOptional($name)) {
            $this->validateName($name);
        }

        if (!$this->allowOptional($group)) {
            $this->validateGroup($group);
        }

        if (!$this->allowOptional($config)) {
            $this->validateConfiguration($config);
        }

        if ($this->hasErrors()) {
            return null;
        }

        // Set null on empty fields so they are removed from the parameters
        $name = (strlen($name) > 0) ? $name : null;
        $group = (strlen($group) > 0) ? $group : null;
        $config = (strlen($config) > 0) ? $config : null;
        $region = (strlen($region) > 0) ? $region : null;

        $target
            ->withParameter(Target::PARAM_APP, $name)
            ->withParameter(Target::PARAM_GROUP, $group)
            ->withParameter(Target::PARAM_CONFIG, $config)
            ->withParameter('region', $region);

        return $target;
    }

    /**
     * @inheritDoc
     */
    public function isEditValid(Target $target, array $parameters): ?Target
    {
        $this->resetErrors();

        $region = $parameters['aws_region'] ?? '';

        $name = trim($parameters['cd_name'] ?? '');
        $group = trim($parameters['cd_environment'] ?? '');
        $config = trim($parameters['cd_config'] ?? '');

        $s3Parameters = [
            'aws_region' => $region,
            's3_method' => 'artifact',
            's3_local_path' => '.',
            's3_bucket' => $parameters['s3_bucket'] ?? '',
            's3_remote_path' => $parameters['s3_remote_path'] ?? '',
        ];

        if (!$this->allowOptional($region)) {
            $this->validateRegion($region);
        }

        if ($this->hasErrors()) {
            return null;
        }

        $target = $this->s3Validator->isValid($s3Parameters);
        if (!$target) {
            $this->importErrors($this->s3Validator->errors());
            return null;
        }

        if (!$this->allowOptional($name)) {
            $this->validateName($name);
        }

        if (!$this->allowOptional($group)) {
            $this->validateGroup($group);
        }

        if (!$this->allowOptional($config)) {
            $this->validateConfiguration($config);
        }

        if ($this->hasErrors()) {
            return null;
        }

        // Set null on empty fields so they are removed from the parameters
        $name = (strlen($name) > 0) ? $name : null;
        $group = (strlen($group) > 0) ? $group : null;
        $config = (strlen($config) > 0) ? $config : null;
        $region = (strlen($region) > 0) ? $region : null;

        $target
            ->withParameter(Target::PARAM_APP, $name)
            ->withParameter(Target::PARAM_GROUP, $group)
            ->withParameter(Target::PARAM_CONFIG, $config)
            ->withParameter('region', $region);

        return $target;
    }

    /**
     * @inheritDoc
     */
    public function getFormData(ServerRequestInterface $request, ?Target $target): array
    {
        $data = $request->getParsedBody();

        $type = TargetEnum::TYPE_CD;
        $s3Type = TargetEnum::TYPE_S3;

        if ($target && $request->getMethod() !== 'POST') {
            $data['aws_region'] = $target->parameter('region');

            $data["${type}_name"] = $target->parameter(Target::PARAM_APP);
            $data["${type}_group"] = $target->parameter(Target::PARAM_GROUP);
            $data["${type}_config"] = $target->parameter(Target::PARAM_CONFIG);

            $data["${s3Type}_bucket"] = $target->parameter(Target::PARAM_BUCKET);
            $data["${s3Type}_remote_path"] = $target->parameter(Target::PARAM_REMOTE_PATH);
        }

        return [
            'aws_region' => $data['aws_region'] ?? '',

            "${type}_name" => $data["${type}_name"] ?? '',
            "${type}_group" => $data["${type}_group"] ?? '',
            "${type}_config" => $data["${type}_config"] ?? '',

            "${s3Type}_bucket" => $data["${s3Type}_bucket"] ?? '',
            "${s3Type}_remote_path" => $data["${s3Type}_remote_path"] ?? '',
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

        return self::ALLOW_OPTIONAL == ($this->options & self::ALLOW_OPTIONAL);
    }

    /**
     * @param string $region
     *
     * @return void
     */
    private function validateRegion($region)
    {
        if (!$this->validateIn($region, AWSAuthenticator::$awsRegions)) {
            $this->addError(self::ERR_INVALID_REGION, 'aws_region');
        }
    }

    /**
     * @param string $name
     *
     * @return void
     */
    private function validateName($name)
    {
        if (!$this->validateIsRequired($name) || !$this->validateSanityCheck($name)) {
            $this->addRequiredError('CD Application', 'cd_application');
        }

        if ($this->hasErrors()) {
            return;
        }

        if (!$this->validateCharacterBlacklist($name, self::REGEX_CHARACTER_RELAXED_WHITESPACE)) {
            $error = sprintf(self::ERT_CHARACTERS_RELAXED_WHITESPACE, 'CD Application');
            $this->addError($error, 'cd_application');
        }

        if (!$this->validateLength($name, 1, 100)) {
            $this->addLengthError('CD Application', 1, 100, 'cd_application');
        }
    }

    /**
     * @param string $group
     *
     * @return void
     */
    private function validateGroup($group)
    {
        if (!$this->validateIsRequired($group) || !$this->validateSanityCheck($group)) {
            $this->addRequiredError('CD Group', 'cd_group');
        }

        if ($this->hasErrors()) {
            return;
        }

        if (!$this->validateCharacterBlacklist($group, self::REGEX_CHARACTER_RELAXED_WHITESPACE)) {
            $error = sprintf(self::ERT_CHARACTERS_RELAXED_WHITESPACE, 'CD Group');
            $this->addError($error, 'cd_group');
        }

        if (!$this->validateLength($group, 1, 100)) {
            $this->addLengthError('CD Group', 1, 100, 'cd_group');
        }
    }

    /**
     * @param string $configuration
     *
     * @return void
     */
    private function validateConfiguration($configuration)
    {
        if (!$this->validateIsRequired($configuration) || !$this->validateSanityCheck($configuration)) {
            $this->addRequiredError('CD Configuration', 'cd_config');
        }

        if ($this->hasErrors()) {
            return;
        }

        if (!$this->validateCharacterBlacklist($configuration, self::REGEX_CHARACTER_RELAXED_WHITESPACE)) {
            $error = sprintf(self::ERT_CHARACTERS_RELAXED_WHITESPACE, 'CD Configuration');
            $this->addError($error, 'cd_config');
        }

        if (!$this->validateLength($configuration, 1, 100)) {
            $this->addLengthError('CD Configuration', 1, 100, 'cd_config');
        }
    }
}
