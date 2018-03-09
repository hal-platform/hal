<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Validator\Targets;

use Hal\Core\AWS\AWSAuthenticator;
use Hal\Core\Entity\Target;
use Hal\Core\Type\TargetEnum;
use Hal\UI\Parameters;
use Hal\UI\Utility\OptionTrait;
use Hal\UI\Validator\ValidatorErrorTrait;
use Hal\UI\Validator\ValidatorTrait;
use Psr\Http\Message\ServerRequestInterface;

class S3Validator implements TargetValidatorInterface
{
    use OptionTrait;
    use ValidatorErrorTrait;
    use ValidatorTrait;

    public const ALLOW_OPTIONAL = 1;

    private const REGEX_CHARACTER_STRICT_WHITESPACE = '\f\n\r\t\v ';
    private const REGEX_CHARACTER_RELAXED_WHITESPACE = '\f\n\r\t\v';

    private const ERR_INVALID_REGION = 'Please select an AWS region.';
    private const ERR_INVALID_S3_METHOD = 'S3 method is invalid.';

    private const ERT_CHARACTERS_STRICT_WHITESPACE = '%s must not contain any whitespace';
    private const ERT_CHARACTERS_RELAXED_WHITESPACE = '%s must not contain tabs or newlines';

    /**
     * @inheritDoc
     */
    public function isValid(array $parameters): ?Target
    {
        $this->resetErrors();

        $region = $parameters['aws_region'] ?? '';

        $method = $parameters['s3_method'] ?? '';
        $localPath = trim($parameters['s3_local_path'] ?? '');

        $s3Bucket = trim($parameters['s3_bucket'] ?? '');
        $s3Path = trim($parameters['s3_remote_path'] ?? '');

        if (!$this->allowOptional($region)) {
            $this->validateRegion($region);
        }

        if ($this->hasErrors()) {
            return null;
        }

        if (!$this->allowOptional($method)) {
            $this->validateMethod($method);
        }

        if (!$this->allowOptional($localPath)) {
            $this->validateLocalPath($localPath);
        }

        if (!$this->allowOptional($s3Bucket)) {
            $this->validateBucket($s3Bucket);
        }

        if (!$this->allowOptional($s3Path)) {
            $this->validateS3Path($s3Path);
        }

        if ($this->hasErrors()) {
            return null;
        }

        // Set null on empty fields so they are removed from the parameters
        $method = (strlen($method) > 0) ? $method : null;
        $localPath = (strlen($localPath) > 0) ? $localPath : null;
        $s3Bucket = (strlen($s3Bucket) > 0) ? $s3Bucket : null;
        $s3Path = (strlen($s3Path) > 0) ? $s3Path : null;
        $region = (strlen($region) > 0) ? $region : null;

        $target = (new Target)
            ->withParameter(Parameters::TARGET_S3_METHOD, $method)
            ->withParameter(Parameters::TARGET_S3_BUCKET, $s3Bucket)
            ->withParameter(Parameters::TARGET_S3_LOCAL_PATH, $localPath)
            ->withParameter(Parameters::TARGET_S3_REMOTE_PATH, $s3Path)
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

        $method = $parameters['s3_method'] ?? '';
        $localPath = trim($parameters['s3_local_path'] ?? '');

        $s3Bucket = trim($parameters['s3_bucket'] ?? '');
        $s3Path = trim($parameters['s3_remote_path'] ?? '');

        if (!$this->allowOptional($region)) {
            $this->validateRegion($region);
        }

        if ($this->hasErrors()) {
            return null;
        }

        if (!$this->allowOptional($method)) {
            $this->validateMethod($method);
        }

        if (!$this->allowOptional($localPath)) {
            $this->validateLocalPath($localPath);
        }

        if (!$this->allowOptional($s3Bucket)) {
            $this->validateBucket($s3Bucket);
        }

        if (!$this->allowOptional($s3Path)) {
            $this->validateS3Path($s3Path);
        }

        if ($this->hasErrors()) {
            return null;
        }

        // Set null on empty fields so they are removed from the parameters
        $method = (strlen($method) > 0) ? $method : null;
        $localPath = (strlen($localPath) > 0) ? $localPath : null;
        $s3Bucket = (strlen($s3Bucket) > 0) ? $s3Bucket : null;
        $s3Path = (strlen($s3Path) > 0) ? $s3Path : null;
        $region = (strlen($region) > 0) ? $region : null;

        $target
            ->withParameter(Parameters::TARGET_S3_METHOD, $method)
            ->withParameter(Parameters::TARGET_S3_BUCKET, $s3Bucket)
            ->withParameter(Parameters::TARGET_S3_LOCAL_PATH, $localPath)
            ->withParameter(Parameters::TARGET_S3_REMOTE_PATH, $s3Path)
            ->withParameter(Parameters::TARGET_REGION, $region);

        return $target;
    }

    /**
     * @inheritDoc
     */
    public function getFormData(ServerRequestInterface $request, ?Target $target): array
    {
        $data = $request->getParsedBody();

        $type = TargetEnum::TYPE_S3;

        if ($target && $request->getMethod() !== 'POST') {
            $data['aws_region'] = $target->parameter(Parameters::TARGET_REGION);

            $data["${type}_method"] = $target->parameter(Parameters::TARGET_S3_METHOD);
            $data["${type}_local_path"] = $target->parameter(Parameters::TARGET_S3_LOCAL_PATH);

            $data["${type}_bucket"] = $target->parameter(Parameters::TARGET_S3_BUCKET);
            $data["${type}_remote_path"] = $target->parameter(Parameters::TARGET_S3_REMOTE_PATH);
        }

        return [
            'aws_region' => $data['aws_region'] ?? '',

            "${type}_method" => $data["${type}_method"] ?? '',
            "${type}_local_path" => $data["${type}_local_path"] ?? '',

            "${type}_bucket" => $data["${type}_bucket"] ?? '',
            "${type}_remote_path" => $data["${type}_remote_path"] ?? '',
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
     * @param string $method
     *
     * @return void
     */
    private function validateMethod($method)
    {
        if (!$this->validateIsRequired($method) || !$this->validateSanityCheck($method)) {
            $this->addRequiredError('S3 Method', 's3_method');
            return;
        }

        if (!$this->validateIn($method, Parameters::TARGET_S3_METHODS)) {
            $this->addError(self::ERR_INVALID_S3_METHOD, 's3_method');
        }
    }

    /**
     * @param string $localPath
     *
     * @return void
     */
    private function validateLocalPath($localPath)
    {
        // always optional
        if (strlen($localPath) === 0) {
            return;
        }

        if (!$this->validateCharacterBlacklist($localPath, self::REGEX_CHARACTER_RELAXED_WHITESPACE)) {
            $error = sprintf(self::ERT_CHARACTERS_RELAXED_WHITESPACE, 'S3 Source');
            $this->addError($error, 's3_local_path');
        }

        if (!$this->validateLength($localPath, 0, 100)) {
            $this->addLengthError('S3 Source', 0, 100, 's3_local_path');
        }
    }

    /**
     * @param string $bucket
     *
     * @return void
     */
    private function validateBucket($bucket)
    {
        if (!$this->validateIsRequired($bucket) || !$this->validateSanityCheck($bucket)) {
            $this->addRequiredError('S3 Bucket', 's3_bucket');
            return;
        }

        if (!$this->validateCharacterBlacklist($bucket, self::REGEX_CHARACTER_STRICT_WHITESPACE)) {
            $error = sprintf(self::ERT_CHARACTERS_STRICT_WHITESPACE, 'S3 Bucket');
            $this->addError($error, 's3_bucket');
        }

        if (!$this->validateLength($bucket, 1, 100)) {
            $this->addLengthError('S3 Bucket', 1, 100, 's3_bucket');
        }
    }

    /**
     * @param string $s3Path
     *
     * @return void
     */
    private function validateS3Path($s3Path)
    {
        // always optional
        if (strlen($s3Path) === 0) {
            return;
        }

        if (!$this->validateCharacterBlacklist($s3Path, self::REGEX_CHARACTER_RELAXED_WHITESPACE)) {
            $error = sprintf(self::ERT_CHARACTERS_RELAXED_WHITESPACE, 'S3 Destination');
            $this->addError($error, 's3_remote_path');
        }

        if (!$this->validateLength($s3Path, 0, 100)) {
            $this->addLengthError('S3 Destination', 0, 100, 's3_remote_path');
        }
    }
}
