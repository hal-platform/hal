<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Validator;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\Environment;
use Hal\Core\Entity\Group;
use Hal\Core\Type\GroupEnum;
use Hal\Core\AWS\AWSAuthenticator;

class GroupValidator
{
    use ValidatorErrorTrait;
    use ValidatorTrait;

    private const REGEX_CHARACTER_CLASS_HOST = '[a-zA-Z0-9]{1}[a-zA-Z0-9\.\-]{1,59}(\:[0-9]{1,5})?';

    const ERR_MISSING_TYPE = 'Please select a type.';
    const ERR_MISSING_ENV = 'Please select an environment.';

    const ERR_EB_DUPLICATE = 'An EB group for this environment and region already exists.';
    const ERR_S3_DUPLICATE = 'An S3 group for this environment and region already exists.';
    const ERR_CD_DUPLICATE = 'A CD group for this environment and region already exists.';
    const ERR_SCRIPT_DUPLICATE = 'A script group for this environment already exists.';

    const ERR_INVALID_HOST = 'Invalid hostname.';
    const ERR_MISSING_HOST = 'Hostname is required for rsync groups';
    const ERR_LONG_HOST = 'Hostname must be less than or equal to 60 characters.';

    const ERR_INVALID_REGION = 'Invalid AWS region specified.';

    /**
     * @var EntityRepository
     */
    private $envRepo;
    private $groupRepo;

    /**
     * @var array
     */
    private $awsTypes;

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->envRepo = $em->getRepository(Environment::CLASS);
        $this->groupRepo = $em->getRepository(Group::CLASS);

        $this->awsTypes = [
            GroupEnum::TYPE_EB,
            GroupEnum::TYPE_S3,
            GroupEnum::TYPE_CD
        ];
    }

    /**
     * @param string $groupType
     * @param string $environmentID
     * @param string $hostname
     * @param string $region
     *
     * @return Group|null
     */
    public function isValid(
        string $groupType,
        string $environmentID,
        string $hostname,
        string $region
    ): ?Group {
        $this->resetErrors();

        if (!$this->validateIn($groupType, GroupEnum::options())) {
            $this->addError(self::ERR_MISSING_TYPE, 'type');
        }

        if (!$environmentID || !$environment = $this->envRepo->find($environmentID)) {
            $this->addError(self::ERR_MISSING_ENV, 'environment');
        }

        if ($this->hasErrors()) {
            return null;
        }

        // validate hostname if rsync group
        if ($groupType === GroupEnum::TYPE_RSYNC) {
            $name = trim(strtolower($hostname));
            $name = $this->validateHostname($name);

        // validate duplicate script group for environment
        // Only 1 script type per environment
        } elseif ($groupType === GroupEnum::TYPE_SCRIPT) {
            $name = '';
            $this->dupeCheck($environment, $groupType, $name);

        // validate duplicate AWS group for environment
        // Only 1 aws type per region/environment
        } elseif (in_array($groupType, $this->awsTypes)) {
            $name = trim(strtolower($region));
            $name = $this->validateRegion($name);

            if ($this->hasErrors()) {
                return null;
            }

            $this->dupeCheck($environment, $groupType, $name);

        } else {
            return null;
        }

        if ($this->hasErrors()) {
            return null;
        }

        return (new Group)
            ->withType($groupType)
            ->withEnvironment($environment)
            ->withName($name);
    }

    /**
     * @param Group $group
     * @param string $groupType
     * @param string $environmentID
     * @param string $hostname
     * @param string $region
     *
     * @return Group|null
     */
    public function isEditValid(Group $group, $groupType, $environmentID, $hostname, $region)
    {
        $this->resetErrors();

        if (!in_array($groupType, GroupEnum::options())) {
            $this->addError(self::ERR_MISSING_TYPE, 'type');
        }

        if (!$environmentID || !$environment = $this->envRepo->find($environmentID)) {
            $this->addError(self::ERR_MISSING_ENV, 'environment');
        }

        if ($this->hasErrors()) {
            return null;
        }

        $hasChanged = ($environmentID != $group->environment()->id() || $groupType != $group->type());

        // validate hostname if rsync group
        // RSYNC-hostname (name) pair is unique
        if ($groupType === GroupEnum::TYPE_RSYNC) {
            $name = trim(strtolower($hostname));

            $hasChanged = $hasChanged || ($name != $group->name());
            if (!$hasChanged) {
                goto SKIP_DUPE_CHECK;
            }

            $name = $this->validateHostname($name);

        // validate duplicate script group for environment
        // Only 1 script type per environment
        } elseif ($groupType === GroupEnum::TYPE_SCRIPT) {
            $name = '';

            if (!$hasChanged) {
                goto SKIP_DUPE_CHECK;
            }

            $this->dupeCheck($environment, $groupType, $name);

        // validate duplicate AWS group for environment
        // Only 1 aws type per region/environment
        } elseif (in_array($groupType, $this->awsTypes)) {
            $name = trim(strtolower($region));

            $hasChanged = $hasChanged || ($name != $group->name());
            if (!$hasChanged) {
                goto SKIP_DUPE_CHECK;
            }

            $name = $this->validateRegion($name);

            if ($this->hasErrors()) {
                return null;
            }

            $this->dupeCheck($environment, $groupType, $name);

        } else {
            return null;
        }

        SKIP_DUPE_CHECK:

        if ($this->hasErrors()) {
            return null;
        }


        return $group
            ->withType($groupType)
            ->withEnvironment($environment)
            ->withName($name);
    }

    /**
     * @param string $hostname
     *
     * @return string|null
     */
    private function validateHostname($hostname)
    {
        if (!$this->validateIsRequired($hostname) || !$this->validateSanityCheck($hostname)) {
            $this->addError(self::ERR_MISSING_HOST, 'hostname');
        }

        if (!$this->validateLength($hostname, 1, 60)) {
            $this->addError(self::ERR_LONG_HOST, 'hostname');
        }

        if ($this->hasErrors()) {
            return null;
        }

        if (!$this->validateCharacterWhitelist($hostname, self::REGEX_CHARACTER_CLASS_HOST)) {
            $this->addError(self::ERR_INVALID_HOST, 'hostname');
        }

        if ($this->hasErrors()) {
            return null;
        }

        return $hostname;
    }

    /**
     * @param string $region
     *
     * @return string|null
     */
    private function validateRegion($region)
    {
        if (!$this->validateIn($region, AWSAuthenticator::$awsRegions)) {
            $this->addError(self::ERR_INVALID_REGION, 'region');
        }

        return $region;
    }

    /**
     * @param Environment $environment
     * @param string $type
     * @param string $name
     *
     * @return void
     */
    private function dupeCheck(Environment $environment, $type, $name)
    {
        $dupe = $this->groupRepo->findOneBy([
            'environment' => $environment,
            'type' => $type,
            'name' => $name
        ]);

        if (!$dupe) {
            return;
        }

        if ($type == GroupEnum::TYPE_EB) {
            $this->addError(self::ERR_EB_DUPLICATE);

        } elseif ($type == GroupEnum::TYPE_S3) {
            $this->addError(self::ERR_S3_DUPLICATE);

        } elseif ($type == GroupEnum::TYPE_CD) {
            $this->addError(self::ERR_CD_DUPLICATE);

        } elseif ($type == GroupEnum::TYPE_SCRIPT) {
            $this->addError(self::ERR_SCRIPT_DUPLICATE);
        }
    }
}
