<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Validator;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\Application;
use Hal\Core\Entity\Credential;
use Hal\Core\Entity\Target;
use Hal\Core\Entity\Group;
use Hal\Core\Repository\TargetRepository;
use Hal\Core\Type\GroupEnum;

/**
 * This validator is a pile of shit and stricken with technical debt. Beware.
 */
class TargetValidator
{
    use ValidatorErrorTrait;
    use NewValidatorTrait;

    private const REGEX_CHARACTER_STRICT_WHITESPACE = '\f\n\r\t\v ';
    private const REGEX_CHARACTER_RELAXED_WHITESPACE = '\f\n\r\t\v';
    private const REGEX_CHARACTER_STARTING_SLASH = '#^/#';

    const ERR_CHARACTERS_STRICT_WHITESPACE = '%s must not contain any whitespace';
    const ERR_CHARACTERS_RELAXED_WHITESPACE = '%s must not contain tabs or newlines';

    const ERR_PATH_CHARACTERS_STARTING_SLASH = 'File path must begin with a forward slash (/)';
    const ERR_FILE_CHARACTERS_FILE_DELIMITER = 'S3 File must have a colon (:) separating source and destination';

    const ERR_INVALID_URL = 'URL is invalid.';
    const ERR_INVALID_URL_SCHEME = 'URL scheme is invalid. Please use http or https.';
    const ERR_INVALID_CREDENTIALS = 'Credential is invalid.';
    const ERR_INVALID_SERVER = 'Group is invalid.';
    const ERR_INVALID_S3_METHOD = 'S3 Method is invalid.';

    // Should we still check these in Hal 3?
    // const ERR_DUPLICATE_RSYNC = 'A target already exists for this group and file path.';
    // const ERR_DUPLICATE_CD = 'A target already exists for this CD application and group.';
    // const ERR_DUPLICATE_EB = 'A target already exists for this EB application and environment.';
    // const ERR_DUPLICATE_S3 = 'A target already exists for this S3 bucket and file.';

    /**
     * @var EntityRepository
     */
    private $groupRepo;
    private $targetRepo;
    private $credentialRepo;

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->groupRepo = $em->getRepository(Group::class);
        $this->targetRepo = $em->getRepository(Target::class);
        $this->credentialRepo = $em->getRepository(Credential::class);
    }

    /**
     * @param Application $application
     * @param int $groupID
     *
     * @param string $path
     * @param string $name
     *
     * @param string $cdName
     * @param string $cdGroup
     * @param string $cdConfiguration
     *
     * @param string $ebName
     * @param string $ebEnvironment
     *
     * @param string $s3bucket
     * @param string $s3source
     * @param string $s3destination
     *
     * @param string $scriptContext
     *
     * @param string $url
     * @param string $credentialID
     *
     * @return Target|null
     */
    public function isValid(
        Application $application,
        $groupID,
        $name,
        $path,
        //
        $cdName,
        $cdGroup,
        $cdConfiguration,
        //
        $ebName,
        $ebEnvironment,
        //
        $s3method,
        $s3bucket,
        $s3source,
        $s3destination,
        //
        $scriptContext,
        //
        $url,
        $credentialID
    ) {
        $this->resetErrors();

        $path = trim($path);

        $this->validateRequired($groupID);

        // stop validation if errors
        if ($this->hasErrors()) {
            return null;
        }

        $url = $this->validateUrl($url);
        $this->validateName($name);

        $credential = null;
        if ($credentialID && !$credential = $this->credentialRepo->find($credentialID)) {
            $this->addError(self::ERR_INVALID_CREDENTIALS, 'credentials');
        }

        if ($credential && $credential->isInternal()) {
            $this->addError(self::ERR_INVALID_CREDENTIALS, 'credentials');
        }

        if (!$group = $this->groupRepo->find($groupID)) {
            $this->addError(self::ERR_INVALID_SERVER, 'server');
        }

        if ($group->type() == GroupEnum::TYPE_RSYNC) {
            $this->validatePath($path);

        } elseif ($group->type() == GroupEnum::TYPE_CD) {
            $this->validateCD($cdName, $cdGroup, $cdConfiguration);
            $this->validateS3($s3method, $s3bucket, $s3source, $s3destination);

        } elseif ($group->type() == GroupEnum::TYPE_EB) {
            $this->validateEB($ebName, $ebEnvironment);
            $this->validateS3($s3method, $s3bucket, $s3source, $s3destination);

        } elseif ($group->type() == GroupEnum::TYPE_S3) {
            $this->validateS3($s3method, $s3bucket, $s3source, $s3destination);
        }

        // stop validation if errors
        if ($this->hasErrors()) {
            return null;
        }

        $target = (new Target)
            ->withApplication($application)
            ->withGroup($group)
            ->withName($name)
            ->withURL($url)
            ->withCredential($credential);

        if ($group->type() == GroupEnum::TYPE_SCRIPT) {
            $target->withParameter(Target::PARAM_CONTEXT, $scriptContext);

        } elseif ($group->type() == GroupEnum::TYPE_RSYNC) {
            $this->withPath($target, $path);

        } elseif ($group->type() == GroupEnum::TYPE_CD) {
            $this
                ->withCD($target, $cdName, $cdGroup, $cdConfiguration)
                ->withS3($target, $s3method, $s3bucket, $s3source, $s3destination);

        } elseif ($group->type() == GroupEnum::TYPE_EB) {
            $this
                ->withEB($target, $ebName, $ebEnvironment)
                ->withS3($target, $s3method, $s3bucket, $s3source, $s3destination);

        } elseif ($group->type() == GroupEnum::TYPE_S3) {
            $this->withS3($target, $s3method, $s3bucket, $s3source, $s3destination);
        }

        return $target;
    }

    /**
     * @param Target $target
     * @param string $path
     * @param string $name
     *
     * @param string $cdName
     * @param string $cdGroup
     * @param string $cdConfiguration
     *
     * @param string $ebName
     * @param string $ebEnvironment
     *
     * @param string $s3bucket
     * @param string $s3source
     * @param string $s3destination
     *
     * @param string $scriptContext
     *
     * @param string $url
     * @param string $credentialID
     *
     * @return Target|null
     */
    public function isEditValid(
        Target $target,
        $name,
        $path,
        //
        $cdName,
        $cdGroup,
        $cdConfiguration,
        //
        $ebName,
        $ebEnvironment,
        //
        $s3method,
        $s3bucket,
        $s3source,
        $s3destination,
        //
        $scriptContext,
        //
        $url,
        $credentialID
    ) {
        $this->errors = [];

        $path = trim($path);

        $groupType = $target->group()->type();

        $url = $this->validateUrl($url);
        $this->validateName($name);

        $credential = null;
        if ($credentialID && !$credential = $this->credentialRepo->find($credentialID)) {
            $this->addError(self::ERR_INVALID_CREDENTIALS, 'credentials');
        }

        if ($credential && $credential->isInternal()) {
            $this->addError(self::ERR_INVALID_CREDENTIALS, 'credentials');
        }

        if ($groupType == GroupEnum::TYPE_RSYNC) {
            $this->validatePath($path);

        } elseif ($groupType == GroupEnum::TYPE_CD) {
            $this->validateCD($cdName, $cdGroup, $cdConfiguration);
            $this->validateS3($s3method, $s3bucket, $s3source, $s3destination);

        } elseif ($groupType == GroupEnum::TYPE_EB) {
            $this->validateEB($ebName, $ebEnvironment);
            $this->validateS3($s3method, $s3bucket, $s3source, $s3destination);

        } elseif ($groupType == GroupEnum::TYPE_S3) {
            $this->validateS3($s3method, $s3bucket, $s3source, $s3destination);
        }

        // stop validation if errors
        if ($this->hasErrors()) {
            return null;
        }

        $target
            ->withName($name)
            ->withURL($url)
            ->withCredential($credential);

        if ($groupType == GroupEnum::TYPE_SCRIPT) {
            $target->withParameter(Target::PARAM_CONTEXT, $scriptContext);

        } elseif ($groupType == GroupEnum::TYPE_RSYNC) {
            $this->withPath($target, $path);

        } elseif ($groupType == GroupEnum::TYPE_CD) {
            $this
                ->withCD($target, $cdName, $cdGroup, $cdConfiguration)
                ->withS3($target, $s3method, $s3bucket, $s3source, $s3destination);

        } elseif ($groupType == GroupEnum::TYPE_EB) {
            $this
                ->withEB($target, $ebName, $ebEnvironment)
                ->withS3($target, $s3method, $s3bucket, $s3source, $s3destination);

        } elseif ($groupType == GroupEnum::TYPE_S3) {
            $this->withS3($target, $s3method, $s3bucket, $s3source, $s3destination);
        }

        return $target;
    }

    /**
     * @param int $groupID
     *
     * @return void
     */
    private function validateRequired($groupID)
    {
        if (!$this->validateIsRequired($groupID) || !$this->validateSanityCheck($groupID)) {
            $this->addRequiredError('Group', 'group');
        }

        return $this->hasErrors();
    }

    /**
     * @param string $cdApplication
     * @param string $cdGroup
     * @param string $cdConfiguration
     *
     * @return bool
     */
    private function validateCD($cdApplication, $cdGroup, $cdConfiguration)
    {
        if (!$this->validateIsRequired($cdApplication) || !$this->validateSanityCheck($cdApplication)) {
            $this->addRequiredError('CD Application', 'cd_application');
        }

        if (!$this->validateIsRequired($cdGroup) || !$this->validateSanityCheck($cdGroup)) {
            $this->addRequiredError('CD Group', 'cd_group');
        }

        if (!$this->validateIsRequired($cdConfiguration) || !$this->validateSanityCheck($cdConfiguration)) {
            $this->addRequiredError('CD Configuration', 'cd_config');
        }

        if ($hasErrors = $this->hasErrors()) {
            return $hasErrors;
        }

        if (!$this->validateCharacterBlacklist($cdApplication, self::REGEX_CHARACTER_RELAXED_WHITESPACE)) {
            $error = sprintf(ERR_CHARACTERS_RELAXED_WHITESPACE, 'CD Application');
            $this->addError($error, 'cd_application');
        }

        if (!$this->validateCharacterBlacklist($cdGroup, self::REGEX_CHARACTER_RELAXED_WHITESPACE)) {
            $error = sprintf(ERR_CHARACTERS_RELAXED_WHITESPACE, 'CD Group');
            $this->addError($error, 'cd_group');
        }

        if (!$this->validateCharacterBlacklist($cdConfiguration, self::REGEX_CHARACTER_RELAXED_WHITESPACE)) {
            $error = sprintf(ERR_CHARACTERS_RELAXED_WHITESPACE, 'CD Configuration');
            $this->addError($error, 'cd_config');
        }

        if (!$this->validateLength($cdApplication, 1, 100)) {
            $this->addLengthError('CD Application', 1, 100, 'cd_application');
        }

        if (!$this->validateLength($cdGroup, 1, 100)) {
            $this->addLengthError('CD Group', 1, 100, 'cd_group');
        }

        if (!$this->validateLength($cdConfiguration, 1, 100)) {
            $this->addLengthError('CD Configuration', 1, 100, 'cd_config');
        }

        return $this->hasErrors();
    }

    /**
     * @param string $ebApplication
     * @param string $ebEnvironment
     *
     * @return bool
     */
    private function validateEB($ebApplication, $ebEnvironment)
    {
        if (!$this->validateIsRequired($ebApplication) || !$this->validateSanityCheck($ebApplication)) {
            $this->addRequiredError('EB Application', 'eb_application');
        }

        if (!$this->validateIsRequired($ebEnvironment) || !$this->validateSanityCheck($ebEnvironment)) {
            $this->addRequiredError('EB Environment', 'eb_environment');
        }

        if ($hasErrors = $this->hasErrors()) {
            return $hasErrors;
        }

        if (!$this->validateCharacterBlacklist($ebApplication, self::REGEX_CHARACTER_RELAXED_WHITESPACE)) {
            $error = sprintf(ERR_CHARACTERS_RELAXED_WHITESPACE, 'EB Application');
            $this->addError($error, 'eb_application');
        }

        if (!$this->validateCharacterBlacklist($ebEnvironment, self::REGEX_CHARACTER_RELAXED_WHITESPACE)) {
            $error = sprintf(ERR_CHARACTERS_RELAXED_WHITESPACE, 'EB Environment');
            $this->addError($error, 'eb_environment');
        }

        if (!$this->validateLength($ebApplication, 1, 100)) {
            $this->addLengthError('EB Application', 1, 100, 'eb_application');
        }

        if (!$this->validateLength($ebEnvironment, 1, 100)) {
            $this->addLengthError('EB Environment', 1, 100, 'eb_environment');
        }

        return $this->hasErrors();
    }

    /**
     * @param string $path
     *
     * @return bool
     */
    private function validatePath($path)
    {
        if (!$this->validateIsRequired($path) || !$this->validateSanityCheck($path)) {
            $this->addRequiredError('Path', 'path');
            return true;
        }

        if (!preg_match(self::REGEX_CHARACTER_STARTING_SLASH, $path)) {
            $this->addError(self::ERR_PATH_CHARACTERS_STARTING_SLASH, 'path');
        }

        if (!$this->validateCharacterBlacklist($path, self::REGEX_CHARACTER_STRICT_WHITESPACE)) {
            $error = sprintf(ERR_CHARACTERS_STRICT_WHITESPACE, 'Path');
            $this->addError($error, 'path');
        }

        if (!$this->validateLength($path, 1, 200)) {
            $this->addLengthError('Path', 1, 200, 'path');
        }

        return $this->hasErrors();
    }

    /**
     * @param string $method
     * @param string $bucket
     * @param string $source
     * @param string $destination
     *
     * @return bool
     */
    private function validateS3($method, $bucket, $source, $destination)
    {
        if (!$this->validateIsRequired($method) || !$this->validateSanityCheck($method)) {
            $this->addRequiredError('S3 Method', 's3_method');
            return true;
        }

        if (!$this->validateIn($method, Target::S3_METHODS)) {
            $this->addError(self::ERR_INVALID_S3_METHOD, 's3_method');
        }

        if (!$this->validateIsRequired($bucket) || !$this->validateSanityCheck($bucket)) {
            $this->addRequiredError('S3 Bucket', 's3_bucket');
            return true;
        }

        if (!$this->validateCharacterBlacklist($bucket, self::REGEX_CHARACTER_STRICT_WHITESPACE)) {
            $error = sprintf(self::ERR_CHARACTERS_STRICT_WHITESPACE, 'S3 Bucket');
            $this->addError($error, 's3_bucket');
        }

        if (!$this->validateLength($bucket, 1, 100)) {
            $this->addLengthError('S3 Bucket', 1, 100, 's3_bucket');
        }

        if (strlen($source) > 0) {
            if (!$this->validateCharacterBlacklist($source, self::REGEX_CHARACTER_RELAXED_WHITESPACE)) {
                $error = sprintf(ERR_CHARACTERS_RELAXED_WHITESPACE, 'S3 Source');
                $this->addError($error, 's3_local_path');
            }

            if (!$this->validateLength($source, 0, 100)) {
                $this->addLengthError('S3 Source', 0, 100, 's3_local_path');
            }
        }

        if (strlen($destination) > 0) {
            if (!$this->validateCharacterBlacklist($destination, self::REGEX_CHARACTER_RELAXED_WHITESPACE)) {
                $error = sprintf(ERR_CHARACTERS_RELAXED_WHITESPACE, 'S3 Destination');
                $this->addError($error, 's3_remote_path');
            }

            if (!$this->validateLength($destination, 0, 100)) {
                $this->addLengthError('S3 Destination', 0, 100, 's3_remote_path');
            }
        }

        return $this->hasErrors();
    }

    /**
     * @param string $name
     *
     * @return void
     */
    private function validateName($name)
    {
        if (strlen($name) > 0) {
            if (!$this->validateCharacterBlacklist($name, self::REGEX_CHARACTER_RELAXED_WHITESPACE)) {
                $error = sprintf(ERR_CHARACTERS_RELAXED_WHITESPACE, 'Name');
                $this->addError($error, 'name');
            }

            if (!$this->validateLength($name, 0, 100)) {
                $this->addLengthError('Name', 0, 100, 'name');
            }
        }
    }

    /**
     * @param string $url
     *
     * @return string
     */
    private function validateUrl($url)
    {
        $url = trim($url);

        if (strlen($url) === 0) {
            return $url;
        }

        if (!$this->validateLength($url, 0, 200)) {
            $this->addLengthError($url, 0, 200, 'URL');
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!in_array($scheme, [null, 'http', 'https'], true)) {
            $this->addError(self::ERR_INVALID_URL_SCHEME, 'url');
        }

        if ($scheme === null) {
            $url = 'http://' . $url;
        }

        if ($this->hasErrors()) {
            return null;
        }

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            $this->addError(self::ERR_INVALID_URL, 'url');
        }

        return $url;
    }

    /**
     * @param Target $target
     *
     * @param string $cdApplication
     * @param string $cdGroup
     * @param string $cdConfiguration
     *
     * @return Target
     */
    private function withCD(Target $target, $cdApplication, $cdGroup, $cdConfiguration)
    {
        $type = $target->group()->type();

        if ($type !== GroupEnum::TYPE_CD) {
            $cdApplication = null;
            $cdGroup = null;
            $cdConfiguration = null;
        }

        $target
            ->withParameter(Target::PARAM_APP, $cdApplication)
            ->withParameter(Target::PARAM_GROUP, $cdGroup)
            ->withParameter(Target::PARAM_CONFIG, $cdConfiguration);

        return $this;
    }

    /**
     * @param Target $target
     *
     * @param string $ebApplication
     * @param string $ebEnvironment
     *
     * @return Target
     */
    private function withEB(Target $target, $ebApplication, $ebEnvironment)
    {
        $type = $target->group()->type();

        if ($type !== GroupEnum::TYPE_EB) {
            $ebApplication = null;
            $ebEnvironment = null;
        }

        $target
            ->withParameter(Target::PARAM_APP, $ebApplication)
            ->withParameter(Target::PARAM_ENV, $ebEnvironment);

        return $this;
    }

    /**
     * @param Target $target
     *
     * @param string $path
     *
     * @return Target
     */
    private function withPath(Target $target, $path)
    {
        $type = $target->group()->type();

        if (!in_array($type, [GroupEnum::TYPE_RSYNC], true)) {
            $path = null;
        }

        $target
            ->withParameter(Target::PARAM_REMOTE_PATH, $path);

        return $this;
    }

    /**
     * @param Target $target
     *
     * @param string $s3method
     * @param string $s3bucket
     * @param string $s3source
     * @param string $s3destination
     *
     * @return Target
     */
    private function withS3(Target $target, $s3method, $s3bucket, $s3source, $s3destination)
    {
        $type = $target->group()->type();

        if (!in_array($type, [GroupEnum::TYPE_S3, GroupEnum::TYPE_CD, GroupEnum::TYPE_EB], true)) {
            $s3bucket = null;
            $s3source = null;
            $s3destination = null;
        }

        $target
            ->withParameter(Target::PARAM_S3_METHOD, $s3method)
            ->withParameter(Target::PARAM_BUCKET, $s3bucket)
            ->withParameter(Target::PARAM_LOCAL_PATH, $s3source)
            ->withParameter(Target::PARAM_REMOTE_PATH, $s3destination);

        return $this;
    }
}
