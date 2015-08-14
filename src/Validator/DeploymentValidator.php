<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Validator;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use MCP\DataType\HttpUrl;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Credential;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Server;
use QL\Hal\Core\Repository\DeploymentRepository;
use QL\Hal\Core\Type\EnumType\ServerEnum;

class DeploymentValidator
{
    const ERR_REQUIRED = '"%s" is required.';

    const ERR_INVALID_PATH = 'File path is invalid.';
    const ERR_INVALID_URL = 'URL is invalid.';
    const ERR_INVALID_NAME = 'Name is invalid.';

    const ERR_INVALID_CREDENTIALS = 'Credential is invalid.';
    const ERR_INVALID_SERVER = 'Server is invalid.';
    const ERR_INVALID_EB_ENVIRONMENT = 'EB Environment is invalid.';
    const ERR_INVALID_EC2_POOL = 'EC2 Pool is invalid.';

    const ERR_INVALID_BUCKET = 'S3 Bucket is invalid.';
    const ERR_INVALID_FILE = 'S3 File is invalid.';

    const ERR_DUPLICATE_RSYNC = 'A deployment already exists for this server and file path.';
    const ERR_DUPLICATE_EB = 'A deployment already exists for this EB environment ID.';
    const ERR_INVALID_EB_PROJECT = 'EB Application Name must be configured before adding EB Deployments.';
    const ERR_DUPLICATE_EC2 = 'A deployment already exists for this EC2 Pool.';
    const ERR_DUPLICATE_S3 = 'A deployment already exists for this S3 bucket and file.';

    const DEFAULT_S3_FILE = '$PUSHID.tar.gz';

    /**
     * @type EntityRepository
     */
    private $serverRepo;
    private $deploymentRepo;
    private $credentialRepo;

    /**
     * @type array
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
     * @param string $ebEnvironment
     * @param string $ec2Pool
     * @param string $s3bucket
     * @param string $s3file
     * @param string $url
     *
     * @return Deployment|null
     */
    public function isValid(
        Application $application,
        $serverID,
        $name,
        $path,
        $ebEnvironment,
        $ec2Pool,
        $s3bucket,
        $s3file,
        $url
    ) {
        $this->errors = [];

        $path = trim($path);

        $this->validateRequired($serverID, $url);

        // stop validation if errors
        if ($this->errors) return;

        $this->validateUrl($url);
        $this->validateName($name);

        if (!$server = $this->serverRepo->find($serverID)) {
            $this->errors[] = self::ERR_INVALID_SERVER;
        }

        // stop validation if errors
        if ($this->errors) return;

        if ($server->type() == ServerEnum::TYPE_RSYNC) {
            $this->validatePath($path);

        } elseif ($server->type() == ServerEnum::TYPE_EB) {
            $this->validateEbEnvironment($ebEnvironment);
            $this->validateEbConfigRequired($application);
            $this->validateS3($s3bucket, $s3file);

        } elseif ($server->type() == ServerEnum::TYPE_EC2) {
            $this->validatePath($path);
            $this->validateEc2Pool($ec2Pool);

        } elseif ($server->type() == ServerEnum::TYPE_S3) {
            $this->validateS3($s3bucket, $s3file);
            if (!$s3file) $s3file = self::DEFAULT_S3_FILE;
        }

        // stop validation if errors
        if ($this->errors) return;

        // check dupes
        $this->validateNewDuplicate($server, $ebEnvironment, $ec2Pool, $path, $s3bucket, $s3file);

        // stop validation if errors
        if ($this->errors) return;

        $sanitized = $this->sanitizeProperties($server->type(), $path, $ebEnvironment, $ec2Pool, $s3bucket, $s3file);
        list($path, $ebEnvironment, $ec2Pool, $s3bucket, $s3file) = $sanitized;

        $deployment = (new Deployment)
            ->withApplication($application)
            ->withServer($server)

            ->withName($name)
            ->withPath($path)
            ->withEBEnvironment($ebEnvironment)
            ->withEC2Pool($ec2Pool)
            ->withS3Bucket($s3bucket)
            ->withS3File($s3file)

            ->withUrl($url);

        return $deployment;
    }

    /**
     * @param Deployment $deployment
     * @param string $path
     * @param string $name
     * @param string $ebEnvironment
     * @param string $ec2Pool
     * @param string $s3bucket
     * @param string $s3file
     * @param string $url
     * @param string $credentialID
     *
     * @return Deployment|null
     */
    public function isEditValid(
        Deployment $deployment,
        $name,
        $path,
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

        $this->validateUrl($url);
        $this->validateName($name);

        $credential = null;
        if ($credentialID && !$credential = $this->credentialRepo->find($credentialID)) {
            $this->errors[] = self::ERR_INVALID_CREDENTIALS;
        }

        if ($serverType == ServerEnum::TYPE_RSYNC) {
            $this->validatePath($path);

        } elseif ($serverType == ServerEnum::TYPE_EB) {
            $this->validateEbEnvironment($ebEnvironment);
            $this->validateEbConfigRequired($deployment->application());
            $this->validateS3($s3bucket, $s3file);

        } elseif ($serverType == ServerEnum::TYPE_EC2) {
            $this->validatePath($path);
            $this->validateEc2Pool($ec2Pool);

        } elseif ($serverType == ServerEnum::TYPE_S3) {
            $this->validateS3($s3bucket, $s3file);
            if (!$s3file) $s3file = self::DEFAULT_S3_FILE;
        }

        // stop validation if errors
        if ($this->errors) return;

        // check dupes
        $this->validateCurrentDuplicate($deployment, $ebEnvironment, $ec2Pool, $path, $s3bucket, $s3file);

        // stop validation if errors
        if ($this->errors) return;

        $sanitized = $this->sanitizeProperties($serverType, $path, $ebEnvironment, $ec2Pool, $s3bucket, $s3file);
        list($path, $ebEnvironment, $ec2Pool, $s3bucket, $s3file) = $sanitized;

        $deployment
            ->withName($name)
            ->withPath($path)
            ->withEBEnvironment($ebEnvironment)
            ->withEC2Pool($ec2Pool)
            ->withS3Bucket($s3bucket)
            ->withS3File($s3file)

            ->withUrl($url)
            ->withCredential($credential);

        return $deployment;
    }

    /**
     * @param string $type
     *
     * @param string $path
     * @param string $ebEnvironment
     * @param string $ec2Pool
     * @param string $s3bucket
     * @param string $s3file
     *
     * @return array
     */
    private function sanitizeProperties($type, $path, $ebEnvironment, $ec2Pool, $s3bucket, $s3file)
    {
        if ($type !== ServerEnum::TYPE_S3) {
            $s3file = null;
        }

        if (!in_array($type, [ServerEnum::TYPE_S3, ServerEnum::TYPE_EB], true)) {
            $s3bucket = null;
        }

        if ($type !== ServerEnum::TYPE_EB) {
            $ebEnvironment = null;
        }

        if ($type !== ServerEnum::TYPE_EC2) {
            $ec2Pool = null;
        }

        if (!in_array($type, [ServerEnum::TYPE_RSYNC, ServerEnum::TYPE_EC2], true)) {
            $path = null;
        }

        return [$path, $ebEnvironment, $ec2Pool, $s3bucket, $s3file];
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
     * @param string $ebEnvironment
     * @param string $ec2Pool
     * @param string $path
     * @param string $s3bucket
     * @param string $s3file
     *
     * @return bool
     */
    private function validateCurrentDuplicate(Deployment $deployment, $ebEnvironment, $ec2Pool, $path, $s3bucket, $s3file)
    {
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

        } elseif ($serverType == ServerEnum::TYPE_EB) {

            // EB did not change, skip dupe check
            if ($deployment->ebEnvironment() == $ebEnvironment) {
                goto SKIP_VALIDATION;
            }

            $deployment = $this->deploymentRepo->findOneBy(['ebEnvironment' => $ebEnvironment]);
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
     * @param string $ebEnvironment
     * @param string $ec2Pool
     * @param string $path
     * @param string $s3bucket
     * @param string $s3file
     *
     * @return bool
     */
    private function validateNewDuplicate(Server $server, $ebEnvironment, $ec2Pool, $path, $s3bucket, $s3file)
    {
        $errors = [];

        if ($server->type() == ServerEnum::TYPE_RSYNC) {
            $deployment = $this->deploymentRepo->findOneBy(['server' => $server, 'path' => $path]);
            if ($deployment) {
                $errors[] = self::ERR_DUPLICATE_RSYNC;
            }

        } elseif ($server->type() == ServerEnum::TYPE_EB) {
            $deployment = $this->deploymentRepo->findOneBy(['ebEnvironment' => $ebEnvironment]);
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
     * @param int $serverId
     * @param string $url
     *
     * @return bool
     */
    private function validateRequired($serverId, $url)
    {
        $errors = [];

        if (!$serverId) {
            $errors[] = sprintf(self::ERR_REQUIRED, 'Server');
        }

        if (!$url) {
            $errors[] = sprintf(self::ERR_REQUIRED, 'URL');
        }

        $this->errors = array_merge($this->errors, $errors);
        return count($errors) === 0;
    }

    /**
     * @param string $ebEnvironment
     *
     * @return bool
     */
    private function validateEbEnvironment($ebEnvironment)
    {
        $errors = [];

        if (!$ebEnvironment) {
            $errors[] = sprintf(self::ERR_REQUIRED, 'EB Environment');
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
     * @param Application $application
     *
     * @return bool
     */
    private function validateEbConfigRequired(Application $application)
    {
        $errors = [];

        if (!$application->ebName()) {
            $errors[] = self::ERR_INVALID_EB_PROJECT;
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
     * @return bool
     */
    private function validateName($name)
    {
        $errors = [];

        if (preg_match('#[\t\n]+#', $name) === 1 || strlen($name) > 100) {
            $errors[] = self::ERR_INVALID_NAME;
        }

        $this->errors = array_merge($this->errors, $errors);
        return count($errors) === 0;
    }

    /**
     * @param string $url
     *
     * @return bool
     */
    private function validateUrl($url)
    {
        $errors = [];

        if (strlen($url) > 200) {
            $errors[] = self::ERR_INVALID_URL;
        }

        $url = HttpUrl::create($url);
        if (!$url instanceof HttpUrl) {
            $errors[] = self::ERR_INVALID_URL;
        }

        $this->errors = array_merge($this->errors, $errors);
        return count($errors) === 0;
    }
}
