<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Validator;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Credential;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Server;
use QL\Hal\Core\Repository\DeploymentRepository;
use QL\Hal\Core\Type\EnumType\ServerEnum;

/**
 * This validator is a pile of shit and stricken with technical debt. Beware.
 */
class DeploymentValidator
{
    const ERR_REQUIRED = '"%s" is required.';

    const ERR_INVALID_PATH = 'File path is invalid.';
    const ERR_INVALID_URL = 'URL is invalid.';
    const ERR_INVALID_URL_SCHEME = 'URL scheme is invalid. Please use http or https.';
    const ERR_INVALID_NAME = 'Name is invalid.';

    const ERR_INVALID_CREDENTIALS = 'Credential is invalid.';
    const ERR_INVALID_SERVER = 'Server is invalid.';

    const ERR_INVALID_CD_APPLICATION = 'CD Application is invalid.';
    const ERR_INVALID_CD_GROUP = 'CD Group is invalid.';
    const ERR_INVALID_CD_CONFIG = 'CD Configuration is invalid.';

    const ERR_INVALID_EB_APPLICATION = 'EB Application is invalid.';
    const ERR_INVALID_EB_ENVIRONMENT = 'EB Environment is invalid.';

    const ERR_INVALID_EC2_POOL = 'EC2 Pool is invalid.';

    const ERR_INVALID_BUCKET = 'S3 Bucket is invalid.';
    const ERR_INVALID_FILE = 'S3 File is invalid.';

    const ERR_DUPLICATE_RSYNC = 'A deployment already exists for this server and file path.';
    const ERR_DUPLICATE_CD = 'A deployment already exists for this CD application and group.';
    const ERR_DUPLICATE_EB = 'A deployment already exists for this EB application and environment.';
    const ERR_DUPLICATE_EC2 = 'A deployment already exists for this EC2 Pool.';
    const ERR_DUPLICATE_S3 = 'A deployment already exists for this S3 bucket and file.';

    /**
     * @var EntityRepository
     */
    private $serverRepo;
    private $deploymentRepo;
    private $credentialRepo;

    /**
     * @var array
     */
    private $errors;

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->serverRepo = $em->getRepository(Server::CLASS);
        $this->deploymentRepo = $em->getRepository(Deployment::CLASS);
        $this->credentialRepo = $em->getRepository(Credential::CLASS);

        $this->errors = [];
    }

    /**
     * @param Application $application
     * @param int $serverID
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
     * @param string $ec2Pool
     *
     * @param string $s3bucket
     * @param string $s3file
     *
     * @param string $url
     *
     * @return Deployment|null
     */
    public function isValid(
        Application $application,
        $serverID,
        $name,
        $path,

        $cdName,
        $cdGroup,
        $cdConfiguration,

        $ebName,
        $ebEnvironment,

        $ec2Pool,

        $s3bucket,
        $s3file,

        $url
    ) {
        $this->errors = [];

        $path = trim($path);

        $this->validateRequired($serverID);

        // stop validation if errors
        if ($this->errors) return;

        $url = $this->validateUrl($url);
        $this->validateName($name);

        if (!$server = $this->serverRepo->find($serverID)) {
            $this->errors[] = self::ERR_INVALID_SERVER;
        }

        // stop validation if errors
        if ($this->errors) return;

        if ($server->type() == ServerEnum::TYPE_RSYNC) {
            $this->validatePath($path);

        } elseif ($server->type() == ServerEnum::TYPE_CD) {
            $this->validateCD($cdName, $cdGroup, $cdConfiguration);
            $this->validateS3($s3bucket, $s3file);

        } elseif ($server->type() == ServerEnum::TYPE_EB) {
            $this->validateEB($ebName, $ebEnvironment);
            $this->validateS3($s3bucket, $s3file);

        } elseif ($server->type() == ServerEnum::TYPE_EC2) {
            $this->validatePath($path);
            $this->validateEc2Pool($ec2Pool);

        } elseif ($server->type() == ServerEnum::TYPE_S3) {
            $this->validateS3($s3bucket, $s3file);
        }

        // stop validation if errors
        if ($this->errors) return;

        // check dupes
        $this->validateNewDuplicate(
            $server,
            $cdName,
            $cdGroup,
            $ebName,
            $ebEnvironment,
            $ec2Pool,
            $path,
            $s3bucket,
            $s3file
        );

        // stop validation if errors
        if ($this->errors) return;

        $deployment = (new Deployment)
            ->withApplication($application)
            ->withServer($server)
            ->withName($name)
            ->withUrl($url);

        $this
            ->withCD($deployment, $cdName, $cdGroup, $cdConfiguration)
            ->withEB($deployment, $ebName, $ebEnvironment)
            ->withEC2($deployment, $ec2Pool)
            ->withPath($deployment, $path)
            ->withS3($deployment, $s3bucket, $s3file);

        return $deployment;
    }

    /**
     * @param Deployment $deployment
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
     * @param string $ec2Pool
     *
     * @param string $s3bucket
     * @param string $s3file
     *
     * @param string $url
     * @param string $credentialID
     *
     * @return Deployment|null
     */
    public function isEditValid(
        Deployment $deployment,
        $name,
        $path,

        $cdName,
        $cdGroup,
        $cdConfiguration,

        $ebName,
        $ebEnvironment,

        $ec2Pool,

        $s3bucket,
        $s3file,

        $url,
        $credentialID
    ) {
        $this->errors = [];

        $path = trim($path);

        $serverType = $deployment->server()->type();

        $url = $this->validateUrl($url);
        $this->validateName($name);

        $credential = null;
        if ($credentialID && !$credential = $this->credentialRepo->find($credentialID)) {
            $this->errors[] = self::ERR_INVALID_CREDENTIALS;
        }

        if ($serverType == ServerEnum::TYPE_RSYNC) {
            $this->validatePath($path);

        } elseif ($serverType == ServerEnum::TYPE_CD) {
            $this->validateCD($cdName, $cdGroup, $cdConfiguration);
            $this->validateS3($s3bucket, $s3file);

        } elseif ($serverType == ServerEnum::TYPE_EB) {
            $this->validateEB($ebName, $ebEnvironment);
            $this->validateS3($s3bucket, $s3file);

        } elseif ($serverType == ServerEnum::TYPE_EC2) {
            $this->validatePath($path);
            $this->validateEc2Pool($ec2Pool);

        } elseif ($serverType == ServerEnum::TYPE_S3) {
            $this->validateS3($s3bucket, $s3file);
        }

        // stop validation if errors
        if ($this->errors) return;

        // check dupes
        $this->validateCurrentDuplicate(
            $deployment,
            $cdName,
            $cdGroup,
            $ebName,
            $ebEnvironment,
            $ec2Pool,
            $path,
            $s3bucket,
            $s3file
        );

        // stop validation if errors
        if ($this->errors) return;

        $deployment
            ->withName($name)
            ->withPath($path)
            ->withUrl($url)
            ->withCredential($credential);

        $this
            ->withCD($deployment, $cdName, $cdGroup, $cdConfiguration)
            ->withEB($deployment, $ebName, $ebEnvironment)
            ->withEC2($deployment, $ec2Pool)
            ->withPath($deployment, $path)
            ->withS3($deployment, $s3bucket, $s3file);

        return $deployment;
    }

    /**
     * @return array
     */
    public function errors()
    {
        return $this->errors;
    }

    /**
     * @param Deployment $deployment
     *
     * @param string $cdName
     * @param string $cdGroup
     *
     * @param string $ebName
     * @param string $ebEnvironment
     *
     * @param string $ec2Pool
     * @param string $path
     *
     * @param string $s3bucket
     * @param string $s3file
     *
     * @return bool
     */
    private function validateCurrentDuplicate(
        Deployment $deployment,
        $cdName,
        $cdGroup,

        $ebName,
        $ebEnvironment,

        $ec2Pool,
        $path,

        $s3bucket,
        $s3file
    ) {
        $errors = [];

        $server = $deployment->server();
        $serverType = $server->type();

        if ($serverType == ServerEnum::TYPE_RSYNC) {

            // rsync path did not change, skip dupe check
            if ($deployment->path() == $path) {
                goto SKIP_VALIDATION;
            }

            $deployment = $this->deploymentRepo->findOneBy(['server' => $server, 'path' => $path]);
            if ($deployment) {
                $errors[] = self::ERR_DUPLICATE_RSYNC;
            }

        } elseif ($serverType == ServerEnum::TYPE_CD) {

            // CD did not change, skip dupe check
            if ($deployment->cdName() == $cdName && $deployment->cdGroup() == $cdGroup) {
                goto SKIP_VALIDATION;
            }

            $deployment = $this->deploymentRepo->findOneBy(['cdName' => $cdName, 'cdGroup' => $cdGroup]);
            if ($deployment) {
                $errors[] = self::ERR_DUPLICATE_CD;
            }

        } elseif ($serverType == ServerEnum::TYPE_EB) {

            // EB did not change, skip dupe check
            if ($deployment->ebName() == $ebName && $deployment->ebEnvironment() == $ebEnvironment) {
                goto SKIP_VALIDATION;
            }

            $deployment = $this->deploymentRepo->findOneBy(['ebName' => $ebName, 'ebEnvironment' => $ebEnvironment]);
            if ($deployment) {
                $errors[] = self::ERR_DUPLICATE_EB;
            }

        } elseif ($serverType == ServerEnum::TYPE_EC2) {

            // EC2 did not change, skip dupe check
            if ($deployment->ec2Pool() == $ec2Pool) {
                goto SKIP_VALIDATION;
            }

            $deployment = $this->deploymentRepo->findOneBy(['ec2Pool' => $ec2Pool]);
            if ($deployment) {
                $errors[] = self::ERR_DUPLICATE_EC2;
            }

        } elseif ($server->type() == ServerEnum::TYPE_S3) {

            // S3 did not change, skip dupe check
            if ($deployment->s3bucket() == $s3bucket && $deployment->s3file() == $s3file) {
                goto SKIP_VALIDATION;
            }

            $deployment = $this->deploymentRepo->findOneBy(['s3bucket' => $s3bucket, 's3file' => $s3file]);
            if ($deployment) {
                $errors[] = self::ERR_DUPLICATE_S3;
            }
        }

        SKIP_VALIDATION:

        $this->errors = array_merge($this->errors, $errors);
        return count($errors) === 0;
    }

    /**
     * @param Server $server
     *
     * @param string $cdName
     * @param string $cdGroup
     *
     * @param string $ebName
     * @param string $ebEnvironment
     *
     * @param string $ec2Pool
     * @param string $path
     * @param string $s3bucket
     * @param string $s3file
     *
     * @return bool
     */
    private function validateNewDuplicate(
        Server $server,
        $cdName,
        $cdGroup,

        $ebName,
        $ebEnvironment,

        $ec2Pool,
        $path,

        $s3bucket,
        $s3file
    ) {
        $errors = [];

        if ($server->type() == ServerEnum::TYPE_RSYNC) {
            $deployment = $this->deploymentRepo->findOneBy(['server' => $server, 'path' => $path]);
            if ($deployment) {
                $errors[] = self::ERR_DUPLICATE_RSYNC;
            }

        } elseif ($server->type() == ServerEnum::TYPE_CD) {
            $deployment = $this->deploymentRepo->findOneBy(['cdName' => $cdName, 'cdGroup' => $cdGroup]);
            if ($deployment) {
                $errors[] = self::ERR_DUPLICATE_EB;
            }

        } elseif ($server->type() == ServerEnum::TYPE_EB) {
            $deployment = $this->deploymentRepo->findOneBy(['ebName' => $ebName, 'ebEnvironment' => $ebEnvironment]);
            if ($deployment) {
                $errors[] = self::ERR_DUPLICATE_EB;
            }

        } elseif ($server->type() == ServerEnum::TYPE_EC2) {
            $deployment = $this->deploymentRepo->findOneBy(['ec2Pool' => $ec2Pool]);
            if ($deployment) {
                $errors[] = self::ERR_DUPLICATE_EC2;
            }

        } elseif ($server->type() == ServerEnum::TYPE_S3) {
            $deployment = $this->deploymentRepo->findOneBy(['s3bucket' => $s3bucket, 's3file' => $s3file]);
            if ($deployment) {
                $errors[] = self::ERR_DUPLICATE_S3;
            }
        }

        $this->errors = array_merge($this->errors, $errors);
        return count($errors) === 0;
    }

    /**
     * @param int $serverID
     *
     * @return void
     */
    private function validateRequired($serverID)
    {
        if (!$serverID) {
            $this->errors[] = sprintf(self::ERR_REQUIRED, 'Server');
        }
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
        $errors = [];

        if (!$cdApplication) {
            $errors[] = sprintf(self::ERR_REQUIRED, 'CD Application');
        }

        if (!$cdGroup) {
            $errors[] = sprintf(self::ERR_REQUIRED, 'CD Group');
        }

        if (!$cdConfiguration) {
            $errors[] = sprintf(self::ERR_REQUIRED, 'CD Configuration');
        }

        if (preg_match('#[\t\n]+#', $cdApplication) === 1 || strlen($cdApplication) > 100) {
            $errors[] = self::ERR_INVALID_CD_APPLICATION;
        }

        if (preg_match('#[\t\n]+#', $cdGroup) === 1 || strlen($cdGroup) > 100) {
            $errors[] = self::ERR_INVALID_CD_GROUP;
        }

        if (preg_match('#[\t\n]+#', $cdConfiguration) === 1 || strlen($cdConfiguration) > 100) {
            $errors[] = self::ERR_INVALID_CD_CONFIG;
        }

        $this->errors = array_merge($this->errors, $errors);
        return count($errors) === 0;
    }

    /**
     * @param string $ebApplication
     * @param string $ebEnvironment
     *
     * @return bool
     */
    private function validateEB($ebApplication, $ebEnvironment)
    {
        $errors = [];

        if (!$ebApplication) {
            $errors[] = sprintf(self::ERR_REQUIRED, 'EB Application');
        }

        if (!$ebEnvironment) {
            $errors[] = sprintf(self::ERR_REQUIRED, 'EB Environment');
        }

        if (preg_match('#[\t\n]+#', $ebApplication) === 1 || strlen($ebApplication) > 100) {
            $errors[] = self::ERR_INVALID_EB_APPLICATION;
        }

        if (preg_match('#[\t\n]+#', $ebEnvironment) === 1 || strlen($ebEnvironment) > 100) {
            $errors[] = self::ERR_INVALID_EB_ENVIRONMENT;
        }

        $this->errors = array_merge($this->errors, $errors);
        return count($errors) === 0;
    }

    /**
     * @param string $ec2Pool
     *
     * @return bool
     */
    private function validateEc2Pool($ec2Pool)
    {
        $errors = [];

        if (!$ec2Pool) {
            $errors[] = sprintf(self::ERR_REQUIRED, 'EC2 Pool');
        }

        if (preg_match('#[\t\n]+#', $ec2Pool) === 1 || strlen($ec2Pool) > 100) {
            $errors[] = self::ERR_INVALID_EC2_POOL;
        }

        $this->errors = array_merge($this->errors, $errors);
        return count($errors) === 0;
    }

    /**
     * @param string $path
     *
     * @return bool
     */
    private function validatePath($path)
    {
        $errors = [];

        if (!$path) {
            $errors[] = sprintf(self::ERR_REQUIRED, 'Path');
        }

        if (strlen($path) > 200) {
            $errors[] = self::ERR_INVALID_PATH;
        }

        if (substr($path, 0, 1) !== '/') {
            $errors[] = self::ERR_INVALID_PATH;
        }

        if (preg_match('#[\t\n]+#', $path) === 1) {
            $errors[] = self::ERR_INVALID_PATH;
        }

        $this->errors = array_merge($this->errors, $errors);
        return count($errors) === 0;
    }

    /**
     * @param string $bucket
     * @param string $file
     *
     * @return bool
     */
    private function validateS3($bucket, $file)
    {
        $errors = [];

        if (!$bucket) {
            $errors[] = sprintf(self::ERR_REQUIRED, 'Bucket');
        }

        if (preg_match('#[\t\n]+#', $bucket) === 1 || strlen($bucket) > 100) {
            $errors[] = self::ERR_INVALID_BUCKET;
        }

        if (strlen($file) > 0) {

            if (preg_match('#[\t\n]+#', $file) === 1 || strlen($file) > 100) {
                $errors[] = self::ERR_INVALID_FILE;
            }
        }

        $this->errors = array_merge($this->errors, $errors);
        return count($errors) === 0;
    }

    /**
     * @param string $name
     *
     * @return void
     */
    private function validateName($name)
    {
        if (preg_match('#[\t\n]+#', $name) === 1 || strlen($name) > 100) {
            $this->errors[] = self::ERR_INVALID_NAME;
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

        if (strlen($url) > 200) {
            $this->errors[] = self::ERR_INVALID_URL;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!in_array($scheme, [null, 'http', 'https'], true)) {
            $this->errors[] = self::ERR_INVALID_URL_SCHEME;
        }

        if ($scheme === null) {
            $url = 'http://' . $url;
        }

        if ($this->errors) return '';

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            $this->errors[] = self::ERR_INVALID_URL;
        }

        return $url;
    }

    /**
     * @param Deployment $deployment
     *
     * @param string $cdApplication
     * @param string $cdGroup
     * @param string $cdConfiguration
     *
     * @return Deployment
     */
    private function withCD(Deployment $deployment, $cdApplication, $cdGroup, $cdConfiguration)
    {
        $type = $deployment->server()->type();

        if ($type !== ServerEnum::TYPE_CD) {
            $cdApplication = null;
            $cdGroup = null;
            $cdConfiguration = null;
        }

        $deployment
            ->withCDName($cdApplication)
            ->withCDGroup($cdGroup)
            ->withCDConfiguration($cdConfiguration);

        return $this;
    }

    /**
     * @param Deployment $deployment
     *
     * @param string $ebApplication
     * @param string $ebEnvironment
     *
     * @return Deployment
     */
    private function withEB(Deployment $deployment, $ebApplication, $ebEnvironment)
    {
        $type = $deployment->server()->type();

        if ($type !== ServerEnum::TYPE_EB) {
            $ebApplication = null;
            $ebEnvironment = null;
        }

        $deployment
            ->withEBName($ebApplication)
            ->withEBEnvironment($ebEnvironment);

        return $this;
    }

    /**
     * @param Deployment $deployment
     *
     * @param string $ec2Pool
     *
     * @return Deployment
     */
    private function withEC2(Deployment $deployment, $ec2Pool)
    {
        $type = $deployment->server()->type();

        if ($type !== ServerEnum::TYPE_EC2) {
            $ec2Pool = null;
        }

        $deployment
            ->withEC2Pool($ec2Pool);

        return $this;
    }

    /**
     * @param Deployment $deployment
     *
     * @param string $path
     *
     * @return Deployment
     */
    private function withPath(Deployment $deployment, $path)
    {
        $type = $deployment->server()->type();

        if (!in_array($type, [ServerEnum::TYPE_RSYNC, ServerEnum::TYPE_EC2], true)) {
            $path = null;
        }

        $deployment
            ->withPath($path);

        return $this;
    }

    /**
     * @param Deployment $deployment
     *
     * @param string $s3bucket
     * @param string $s3file
     *
     * @return Deployment
     */
    private function withS3(Deployment $deployment, $s3bucket, $s3file)
    {
        $type = $deployment->server()->type();

        if (!in_array($type, [ServerEnum::TYPE_S3, ServerEnum::TYPE_CD, ServerEnum::TYPE_EB], true)) {
            $s3bucket = null;
            $s3file = null;
        }

        $deployment
            ->withS3Bucket($s3bucket)
            ->withS3File($s3file);

        return $this;
    }
}
