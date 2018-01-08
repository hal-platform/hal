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

class ElasticBeanstalkValidator implements TargetValidatorInterface
{
    use ValidatorErrorTrait;
    use ValidatorTrait;

    private const REGEX_CHARACTER_RELAXED_WHITESPACE = '\f\n\r\t\v';

    private const ERR_INVALID_REGION = 'Please select an AWS region.';
    private const ERT_CHARACTERS_RELAXED_WHITESPACE = '%s must not contain tabs or newlines';

    /**
     * @var S3Validator
     */
    private $s3Validator;

    /**
     * @param S3Validator $s3validator
     */
    public function __construct(S3Validator $s3validator)
    {
        $this->s3Validator = $s3validator;
    }

    /**
     * @inheritDoc
     */
    public function isValid(array $parameters): ?Target
    {
        $this->resetErrors();

        $region = $parameters['aws_region'] ?? '';

        $name = trim($parameters['eb_name'] ?? '');
        $environment = trim($parameters['eb_environment'] ?? '');

        $s3Parameters = [
            'aws_region' => $region,
            's3_method' => 'artifact',
            's3_local_path' => '.',
            's3_bucket' => $parameters['s3_bucket'] ?? '',
            's3_remote_path' => $parameters['s3_remote_path'] ?? '',
        ];

        if (!$this->validateIn($region, AWSAuthenticator::$awsRegions)) {
            $this->addError(self::ERR_INVALID_REGION, 'aws_region');
        }

        if ($this->hasErrors()) {
            return null;
        }

        $target = $this->s3Validator->isValid($s3Parameters);
        if (!$target) {
            $this->importErrors($this->s3Validator->errors());
            return null;
        }

        $this->validateEB($name, $environment);
        if ($this->hasErrors()) {
            return null;
        }

        $target
            ->withParameter(Target::PARAM_APP, $name)
            ->withParameter(Target::PARAM_ENV, $environment)
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

        $name = trim($parameters['eb_name'] ?? '');
        $environment = trim($parameters['eb_environment'] ?? '');

        $s3Parameters = [
            'aws_region' => $region,
            's3_method' => 'artifact',
            's3_local_path' => '.',
            's3_bucket' => $parameters['s3_bucket'] ?? '',
            's3_remote_path' => $parameters['s3_remote_path'] ?? '',
        ];

        if (!$this->validateIn($region, AWSAuthenticator::$awsRegions)) {
            $this->addError(self::ERR_INVALID_REGION, 'aws_region');
        }

        if ($this->hasErrors()) {
            return null;
        }

        $target = $this->s3Validator->isValid($s3Parameters);
        if (!$target) {
            $this->importErrors($this->s3Validator->errors());
            return null;
        }

        $this->validateEB($name, $environment);
        if ($this->hasErrors()) {
            return null;
        }

        $target
            ->withParameter(Target::PARAM_APP, $name)
            ->withParameter(Target::PARAM_ENV, $environment)
            ->withParameter('region', $region);

        return $target;
    }

    /**
     * @inheritDoc
     */
    public function getFormData(ServerRequestInterface $request, ?Target $target): array
    {
        $data = $request->getParsedBody();

        $type = TargetEnum::TYPE_EB;
        $s3Type = TargetEnum::TYPE_S3;

        if ($target && $request->getMethod() !== 'POST') {
            $data['aws_region'] = $target->parameter('region');

            $data["${type}_name"] = $target->parameter(Target::PARAM_APP);
            $data["${type}_environment"] = $target->parameter(Target::PARAM_ENV);

            $data["${s3Type}_bucket"] = $target->parameter(Target::PARAM_BUCKET);
            $data["${s3Type}_remote_path"] = $target->parameter(Target::PARAM_REMOTE_PATH);
        }

        return [
            'aws_region' => $data['aws_region'] ?? '',

            "${type}_name" => $data["${type}_name"] ?? '',
            "${type}_environment" => $data["${type}_environment"] ?? '',

            "${s3Type}_bucket" => $data["${s3Type}_bucket"] ?? '',
            "${s3Type}_remote_path" => $data["${s3Type}_remote_path"] ?? '',
        ];
    }

    /**
     * @param string $applicationName
     * @param string $environmentName
     *
     * @return void
     */
    private function validateEB($applicationName, $environmentName)
    {
        if (!$this->validateIsRequired($applicationName) || !$this->validateSanityCheck($applicationName)) {
            $this->addRequiredError('EB Application', 'eb_application');
        }

        if (!$this->validateIsRequired($environmentName) || !$this->validateSanityCheck($environmentName)) {
            $this->addRequiredError('EB Environment', 'eb_environment');
        }

        if ($this->hasErrors()) {
            return;
        }

        if (!$this->validateCharacterBlacklist($applicationName, self::REGEX_CHARACTER_RELAXED_WHITESPACE)) {
            $error = sprintf(self::ERT_CHARACTERS_RELAXED_WHITESPACE, 'EB Application');
            $this->addError($error, 'eb_application');
        }

        if (!$this->validateCharacterBlacklist($environmentName, self::REGEX_CHARACTER_RELAXED_WHITESPACE)) {
            $error = sprintf(self::ERT_CHARACTERS_RELAXED_WHITESPACE, 'EB Environment');
            $this->addError($error, 'eb_environment');
        }

        if (!$this->validateLength($applicationName, 1, 100)) {
            $this->addLengthError('EB Application', 1, 100, 'eb_application');
        }

        if (!$this->validateLength($environmentName, 1, 100)) {
            $this->addLengthError('EB Environment', 1, 100, 'eb_environment');
        }
    }
}
