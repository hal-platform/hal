<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Validator;

use QL\Hal\Core\Entity\Application;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use MCP\DataType\HttpUrl;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Server;
use QL\Hal\Core\Repository\DeploymentRepository;
use QL\Hal\Core\Type\EnumType\ServerEnum;

class DeploymentValidator
{
    const ERR_REQUIRED = '"%s" is required.';

    const ERR_INVALID_PATH = 'File path is invalid.';
    const ERR_INVALID_URL = 'URL is invalid.';
    const ERR_INVALID_REPO = 'Repository is invalid.';
    const ERR_INVALID_SERVER = 'Server is invalid.';
    const ERR_INVALID_EB_ENVIRONMENT = 'EB Environment is invalid.';
    const ERR_INVALID_EC2_POOL = 'EC2 Pool is invalid.';

    const ERR_DUPLICATE_RSYNC = 'A deployment already exists for this server and file path.';
    const ERR_DUPLICATE_EB = 'A deployment already exists for this EB environment ID.';
    const ERR_INVALID_EB_PROJECT = 'EB Application Name must be configured before adding EB Deployments.';
    const ERR_DUPLICATE_EC2 = 'A deployment already exists for this EC2 Pool.';

    /**
     * @type EntityRepository
     */
    private $applicationRepo;
    private $serverRepo;
    private $deploymentRepo;

    /**
     * @type array
     */
    private $errors;

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->applicationRepo = $em->getRepository(Application::CLASS);
        $this->serverRepo = $em->getRepository(Server::CLASS);
        $this->deploymentRepo = $em->getRepository(Deployment::CLASS);

        $this->errors = [];
    }

    /**
     * @param int $applicationId
     * @param int $serverId
     *
     * @param string $path
     * @param string $ebEnvironment
     * @param string $ec2Pool
     * @param string $url
     *
     * @return Deployment|null
     */
    public function isValid($applicationId, $serverId, $path, $ebEnvironment, $ec2Pool, $url)
    {
        $this->errors = [];

        $this->validateRequired($applicationId, $serverId, $url);

        // stop validation if errors
        if ($this->errors) {
            return null;
        }

        if (!$application = $this->applicationRepo->find($applicationId)) {
            $this->errors[] = self::ERR_INVALID_REPO;
        }

        if (!$server = $this->serverRepo->find($serverId)) {
            $this->errors[] = self::ERR_INVALID_SERVER;
        }

        // stop validation if errors
        if ($this->errors) {
            return null;
        }

        if ($server->type() == ServerEnum::TYPE_RSYNC) {
            $this->validatePath($path);

        } elseif ($server->type() == ServerEnum::TYPE_EB) {
            $this->validateEbEnvironment($ebEnvironment);
            $this->validateEbConfigRequired($application);

        } elseif ($server->type() == ServerEnum::TYPE_EC2) {
            $this->validatePath($path);
            $this->validateEc2Pool($ec2Pool);
        }

        // stop validation if errors
        if ($this->errors) {
            return null;
        }

        // check dupes
        $this->validateNewDuplicate($server, $ebEnvironment, $ec2Pool, $path);

        // stop validation if errors
        if ($this->errors) {
            return null;
        }

        // Wipe eb, ec2  for RSYNC server
        // Wipe path, ec2 for EB servers
        // Wipe path, eb  for EC2 server
        $serverType = $server->type();
        if ($serverType === ServerEnum::TYPE_RSYNC) {
            $ebEnvironment = $ec2Pool = null;

        } else if ($serverType === ServerEnum::TYPE_EB) {
            $path = $ec2Pool = null;

        } else if ($serverType === ServerEnum::TYPE_EC2) {
            $ebEnvironment = null;
        }

        $deployment = (new Deployment)
            ->withRepository($application)
            ->withServer($server)

            ->withPath($path)
            ->withEbEnvironment($ebEnvironment)
            ->withEc2Pool($ec2Pool)

            ->withUrl(HttpUrl::create($url));

        return $deployment;
    }

    /**
     * @param Deployment $deployment
     * @param string $path
     * @param string $ebEnvironment
     * @param string $ec2Pool
     * @param string $url
     *
     * @return Deployment|null
     */
    public function isEditValid(Deployment $deployment, $path, $ebEnvironment, $ec2Pool, $url)
    {
        $this->errors = [];

        $serverType = $deployment->server()->type();

        $this->validateUrl($url);

        if ($serverType == ServerEnum::TYPE_RSYNC) {
            $this->validatePath($path);

        } elseif ($serverType == ServerEnum::TYPE_EB) {
            $this->validateEbEnvironment($ebEnvironment);
            $this->validateEbConfigRequired($deployment->application());

        } elseif ($serverType == ServerEnum::TYPE_EC2) {
            $this->validatePath($path);
            $this->validateEc2Pool($ec2Pool);
        }

        // check dupes
        $this->validateCurrentDuplicate($deployment, $ebEnvironment, $ec2Pool, $path);

        // stop validation if errors
        if ($this->errors) {
            return null;
        }

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
     * @param string $ebEnvironment
     * @param string $ec2Pool
     * @param string $path
     *
     * @return bool
     */
    private function validateCurrentDuplicate(Deployment $deployment, $ebEnvironment, $ec2Pool, $path)
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
     *
     * @return bool
     */
    private function validateNewDuplicate(Server $server, $ebEnvironment, $ec2Pool, $path)
    {
        $errors = [];

        if ($server->type() == ServerEnum::TYPE_RSYNC) {
            $deployment = $this->deploymentRepo->findOneBy(['server' => $server, 'path' => $path]);
            if ($deployment) {
                $errors[] = self::ERR_DUPLICATE_RSYNC;
            }

        } elseif ($server->type() == ServerEnum::TYPE_EB) {
            $this->validateEbEnvironment($ebEnvironment);
            $deployment = $this->deploymentRepo->findOneBy(['ebEnvironment' => $ebEnvironment]);
            if ($deployment) {
                $errors[] = self::ERR_DUPLICATE_EB;
            }

        } elseif ($server->type() == ServerEnum::TYPE_EC2) {
            $this->validateEc2Pool($ec2Pool);
            $deployment = $this->deploymentRepo->findOneBy(['ec2Pool' => $ec2Pool]);
            if ($deployment) {
                $errors[] = self::ERR_DUPLICATE_EC2;
            }
        }

        $this->errors = array_merge($this->errors, $errors);
        return count($errors) === 0;
    }

    /**
     * @param int $applicationId
     * @param int $serverId
     * @param string $url
     *
     * @return bool
     */
    private function validateRequired($applicationId, $serverId, $url)
    {
        $errors = [];

        if (!$applicationId) {
            $errors[] = sprintf(self::ERR_REQUIRED, 'Application');
        }

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

        if (preg_match('#[\t\n]+#', $ebEnvironment) === 1) {
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

        if (preg_match('#[\t\n]+#', $ec2Pool) === 1) {
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
     * @param string $url
     *
     * @return bool
     */
    private function validateUrl($url)
    {
        $errors = [];

        $url = HttpUrl::create($url);

        if (!$url instanceof HttpUrl) {
            $errors[] = self::ERR_INVALID_URL;
        }

        $this->errors = array_merge($this->errors, $errors);
        return count($errors) === 0;
    }
}
