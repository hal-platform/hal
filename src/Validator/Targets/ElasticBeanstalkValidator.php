<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Validator\Targets;

use Hal\Core\AWS\AWSAuthenticator;
use Hal\Core\Entity\Target;
use Hal\Core\Parameters;
use Hal\Core\Type\TargetEnum;
use Hal\UI\Utility\OptionTrait;
use Hal\UI\Validator\ValidatorErrorTrait;
use Hal\UI\Validator\ValidatorTrait;
use Psr\Http\Message\ServerRequestInterface;

class ElasticBeanstalkValidator implements TargetValidatorInterface
{
    use OptionTrait;
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

        if (!$this->allowOptional($environment)) {
            $this->validateEnvironment($environment);
        }

        if ($this->hasErrors()) {
            return null;
        }

        // Set null on empty fields so they are removed from the parameters
        $name = (strlen($name) > 0) ? $name : null;
        $environment = (strlen($environment) > 0) ? $environment : null;
        $region = (strlen($region) > 0) ? $region : null;

        $target
            ->withParameter(Parameters::TARGET_EB_APP, $name)
            ->withParameter(Parameters::TARGET_EB_ENV, $environment)
            ->withParameter(Parameters::TARGET_REGION, $region);

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

        if (!$this->allowOptional($region)) {
            $this->validateRegion($region);
        }

        if ($this->hasErrors()) {
            return null;
        }

        $target = $this->s3Validator->isEditValid($target, $s3Parameters);
        if (!$target) {
            $this->importErrors($this->s3Validator->errors());
            return null;
        }

        if (!$this->allowOptional($name)) {
            $this->validateName($name);
        }

        if (!$this->allowOptional($environment)) {
            $this->validateEnvironment($environment);
        }

        if ($this->hasErrors()) {
            return null;
        }

        // Set null on empty fields so they are removed from the parameters
        $name = (strlen($name) > 0) ? $name : null;
        $environment = (strlen($environment) > 0) ? $environment : null;
        $region = (strlen($region) > 0) ? $region : null;

        $target
            ->withParameter(Parameters::TARGET_EB_APP, $name)
            ->withParameter(Parameters::TARGET_EB_ENV, $environment)
            ->withParameter(Parameters::TARGET_REGION, $region);

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
            $data['aws_region'] = $target->parameter(Parameters::TARGET_REGION);

            $data["${type}_name"] = $target->parameter(Parameters::TARGET_EB_APP);
            $data["${type}_environment"] = $target->parameter(Parameters::TARGET_EB_ENV);

            $data["${s3Type}_bucket"] = $target->parameter(Parameters::TARGET_S3_BUCKET);
            $data["${s3Type}_remote_path"] = $target->parameter(Parameters::TARGET_S3_REMOTE_PATH);
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
     * @param string $value
     *
     * @return bool
     */
    private function allowOptional($value)
    {
        if (strlen($value) > 0) {
            return false;
        }

        return $this->isFlagEnabled(self::ALLOW_OPTIONAL);
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
     * @param string $application
     *
     * @return void
     */
    private function validateName($application)
    {
        if (!$this->validateIsRequired($application) || !$this->validateSanityCheck($application)) {
            $this->addRequiredError('EB Application', 'eb_application');
        }

        if ($this->hasErrors()) {
            return;
        }

        if (!$this->validateCharacterBlacklist($application, self::REGEX_CHARACTER_RELAXED_WHITESPACE)) {
            $error = sprintf(self::ERT_CHARACTERS_RELAXED_WHITESPACE, 'EB Application');
            $this->addError($error, 'eb_application');
        }

        if (!$this->validateLength($application, 1, 100)) {
            $this->addLengthError('EB Application', 1, 100, 'eb_application');
        }
    }

    /**
     * @param string $environment
     *
     * @return void
     */
    private function validateEnvironment($environment)
    {
        if (!$this->validateIsRequired($environment) || !$this->validateSanityCheck($environment)) {
            $this->addRequiredError('EB Environment', 'eb_environment');
        }

        if ($this->hasErrors()) {
            return;
        }

        if (!$this->validateCharacterBlacklist($environment, self::REGEX_CHARACTER_RELAXED_WHITESPACE)) {
            $error = sprintf(self::ERT_CHARACTERS_RELAXED_WHITESPACE, 'EB Environment');
            $this->addError($error, 'eb_environment');
        }

        if (!$this->validateLength($environment, 1, 100)) {
            $this->addLengthError('EB Environment', 1, 100, 'eb_environment');
        }
    }
}
