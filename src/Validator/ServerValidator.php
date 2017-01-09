<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Validator;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\Server;
use QL\Hal\Core\Type\EnumType\ServerEnum;

class ServerValidator
{
    const ERR_MISSING_TYPE = 'Please select a type.';
    const ERR_MISSING_ENV = 'Please select an environment.';

    const ERR_EB_DUPLICATE = 'An EB server for this environment and region already exists.';
    const ERR_EC2_DUPLICATE = 'An EC2 server for this environment and region already exists.';
    const ERR_S3_DUPLICATE = 'An S3 server for this environment and region already exists.';
    const ERR_CD_DUPLICATE = 'A CD server for this environment and region already exists.';

    const ERR_HOST = 'Invalid hostname.';
    const ERR_MISSING_HOST = 'Hostname is required for rsync servers.';
    const ERR_LONG_HOST = 'Hostname must be less than or equal to 60 characters.';

    const ERR_INVALID_REGION = 'Invalid AWS region specified.';

    /**
     * @var EntityRepository
     */
    private $envRepo;
    private $serverRepo;

    /**
     * @var array
     */
    private $errors;

    /**
     * @var array
     */
    private $awsTypes;

    /**
     * Hardcoded, since Enums were removed in aws sdk 3.0
     *
     * @var string[]
     */
    private static $awsRegions = [
        'ap-northeast-1',
        'ap-northeast-2',
        'ap-southeast-1',
        'ap-southeast-2',
        'ap-south-1',

        'ca-central-1',

        'cn-north-1',

        'eu-central-1',
        'eu-west-1',
        'eu-west-2',

        'sa-east-1',

        'us-east-1',
        'us-east-2',
        'us-west-1',
        'us-west-2'
    ];

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->envRepo = $em->getRepository(Environment::CLASS);
        $this->serverRepo = $em->getRepository(Server::CLASS);

        $this->errors = [];

        $this->awsTypes = [
            ServerEnum::TYPE_EB,
            ServerEnum::TYPE_EC2,
            ServerEnum::TYPE_S3,
            ServerEnum::TYPE_CD
        ];
    }

    /**
     * @param string $serverType
     * @param string $environmentID
     * @param string $hostname
     * @param string $region
     *
     * @return Server|null
     */
    public function isValid($serverType, $environmentID, $hostname, $region)
    {
        $this->errors = [];


        if (!in_array($serverType, ServerEnum::values())) {
            $this->errors[] = self::ERR_MISSING_TYPE;
        }

        if (!$environmentID || !$environment = $this->envRepo->find($environmentID)) {
            $this->errors[] = self::ERR_MISSING_ENV;
        }

        if ($this->errors) return;

        // validate hostname if rsync server
        if ($serverType === ServerEnum::TYPE_RSYNC) {

            $name = trim(strtolower($hostname));
            $name = $this->validateHostname($name);

        // validate duplicate AWS server for environment
        // Only 1 aws type per region/environment
        } elseif (in_array($serverType, $this->awsTypes)) {

            $name = trim(strtolower($region));
            $name = $this->validateRegion($name);

            if ($this->errors) return;

            $this->dupeCheck($environment, $serverType, $name);
        }

        if ($this->errors) return;

        return (new Server)
            ->withType($serverType)
            ->withEnvironment($environment)
            ->withName($name);
    }

    /**
     * @param Server $server
     * @param string $serverType
     * @param string $environmentID
     * @param string $hostname
     * @param string $region
     *
     * @return Server|null
     */
    public function isEditValid(Server $server, $serverType, $environmentID, $hostname, $region)
    {
        $this->errors = [];

        if (!in_array($serverType, ServerEnum::values())) {
            $this->errors[] = self::ERR_MISSING_TYPE;
        }

        if (!$environmentID || !$environment = $this->envRepo->find($environmentID)) {
            $this->errors[] = self::ERR_MISSING_ENV;
        }

        if ($this->errors) return;

        $hasChanged = ($environmentID != $server->environment()->id() || $serverType != $server->type());

        // validate hostname if rsync server
        // RSYNC-hostname (name) pair is unique
        if ($serverType === ServerEnum::TYPE_RSYNC) {

            $name = trim(strtolower($hostname));

            $hasChanged = $hasChanged || ($name != $server->name());
            if (!$hasChanged) {
                GOTO SKIP_DUPE_CHECK;
            }

            $name = $this->validateHostname($name);

        // validate duplicate AWS server for environment
        // Only 1 aws type per region/environment
        } elseif (in_array($serverType, $this->awsTypes)) {

            $name = trim(strtolower($region));

            $hasChanged = $hasChanged || ($name != $server->name());
            if (!$hasChanged) {
                GOTO SKIP_DUPE_CHECK;
            }

            $name = $this->validateRegion($name);

            if ($this->errors) return;

            $this->dupeCheck($environment, $serverType, $name);
        }

        SKIP_DUPE_CHECK:

        if ($this->errors) return null;

        return $server
            ->withType($serverType)
            ->withEnvironment($environment)
            ->withName($name);
    }

    /**
     * @return array
     */
    public function errors()
    {
        return $this->errors;
    }

    /**
     * @param string $hostname
     *
     * @return string|null
     */
    private function validateHostname($hostname)
    {
        if (strlen($hostname) === 0) {
            $this->errors[] = self::ERR_MISSING_HOST;
        }

        if (strlen($hostname) > 60) {
            $this->errors[] = self::ERR_LONG_HOST;
        }

        if ($this->errors) return;

        $regex = '[a-zA-Z0-9]{1}' // must start with alphanumeric
            . '[a-zA-Z0-9\.\-]{1,59}'
            . '(\:[0-9]{1,5})?'; // port optional

        if (1 !== preg_match('/^' . $regex . '$/', $hostname)) {
            $this->errors[] = self::ERR_HOST;
            return;
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
        if (!in_array($region, self::$awsRegions, true)) {
            $this->errors[] = self::ERR_INVALID_REGION;
            return;
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
        $dupe = $this->serverRepo->findOneBy([
            'environment' => $environment,
            'type' => $type,
            'name' => $name
        ]);

        if (!$dupe) return;

        if ($type == ServerEnum::TYPE_EB) {
            $this->errors[] = self::ERR_EB_DUPLICATE;

        } elseif ($type == ServerEnum::TYPE_EC2) {
            $this->errors[] = self::ERR_EC2_DUPLICATE;

        } elseif ($type == ServerEnum::TYPE_S3) {
            $this->errors[] = self::ERR_S3_DUPLICATE;

        } elseif ($type == ServerEnum::TYPE_CD) {
            $this->errors[] = self::ERR_CD_DUPLICATE;
        }
    }
}
