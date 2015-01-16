<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Validator;

use MCP\DataType\HttpUrl;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Repository;
use QL\Hal\Core\Entity\Server;
use QL\Hal\Core\Entity\Repository\ServerRepository;
use QL\Hal\Core\Entity\Repository\DeploymentRepository;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Hal\Core\Entity\Type\ServerEnumType;

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
     * @type RepositoryRepository
     */
    private $repoRepo;

    /**
     * @type ServerRepository
     */
    private $serverRepo;

    /**
     * @type DeploymentRepository
     */
    private $deploymentRepo;

    /**
     * @type array
     */
    private $errors;

    /**
     * @param RepositoryRepository $repoRepo
     * @param ServerRepository $serverRepo
     * @param DeploymentRepository $deploymentRepo
     * @param User $currentUser
     */
    public function __construct(
        RepositoryRepository $repoRepo,
        ServerRepository $serverRepo,
        DeploymentRepository $deploymentRepo
    ) {
        $this->repoRepo = $repoRepo;
        $this->serverRepo = $serverRepo;
        $this->deploymentRepo = $deploymentRepo;

        $this->errors = [];
    }

    /**
     * @param int $repositoryId
     * @param int $serverId
     *
     * @param string $path
     * @param string $ebEnvironment
     * @param string $ec2Pool
     * @param string $url
     *
     * @return Deployment|null
     */
    public function isValid($repositoryId, $serverId, $path, $ebEnvironment, $ec2Pool, $url)
    {
        $this->errors = [];

        $this->validateRequired($repositoryId, $serverId, $url);

        // stop validation if errors
        if ($this->errors) {
            return null;
        }

        if (!$repo = $this->repoRepo->find($repositoryId)) {
            $this->errors[] = self::ERR_INVALID_REPO;
        }

        if (!$server = $this->serverRepo->find($serverId)) {
            $this->errors[] = self::ERR_INVALID_SERVER;
        }

        // stop validation if errors
        if ($this->errors) {
            return null;
        }

        if ($server->getType() == ServerEnumType::TYPE_RSYNC) {
            $this->validatePath($path);

        } elseif ($server->getType() == ServerEnumType::TYPE_EB) {
            $this->validateEbEnvironment($ebEnvironment);
            $this->validateEbConfigRequired($repo);

        } elseif ($server->getType() == ServerEnumType::TYPE_EC2) {
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
        $serverType = $server->getType();
        if ($serverType === ServerEnumType::TYPE_RSYNC) {
            $ebEnvironment = $ec2Pool = null;

        } else if ($serverType === ServerEnumType::TYPE_EB) {
            $path = $ec2Pool = null;

        } else if ($serverType === ServerEnumType::TYPE_EC2) {
            $path = $ebEnvironment = null;
        }

        $deployment = new Deployment;
        $deployment->setRepository($repo);
        $deployment->setServer($server);

        $deployment->setPath($path);
        $deployment->setEbEnvironment($ebEnvironment);
        $deployment->setEc2Pool($ec2Pool);

        $deployment->setUrl(HttpUrl::create($url));

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

        $serverType = $deployment->getServer()->getType();

        $this->validateUrl($url);

        if ($serverType == ServerEnumType::TYPE_RSYNC) {
            $this->validatePath($path);

        } elseif ($serverType == ServerEnumType::TYPE_EB) {
            $this->validateEbEnvironment($ebEnvironment);
            $this->validateEbConfigRequired($deployment->getRepository());

        } elseif ($serverType == ServerEnumType::TYPE_EC2) {
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

        $server = $deployment->getServer();
        $serverType = $server->getType();

        if ($serverType == ServerEnumType::TYPE_RSYNC) {

            // rsync path did not change, skip dupe check
            if ($deployment->getPath() == $path) {
                goto SKIP_VALIDATION;
            }

            $deployment = $this->deploymentRepo->findOneBy(['server' => $server, 'path' => $path]);
            if ($deployment) {
                $errors[] = self::ERR_DUPLICATE_RSYNC;
            }

        } elseif ($serverType == ServerEnumType::TYPE_EB) {

            // EB did not change, skip dupe check
            if ($deployment->getEbEnvironment() == $ebEnvironment) {
                goto SKIP_VALIDATION;
            }

            $deployment = $this->deploymentRepo->findOneBy(['ebEnvironment' => $ebEnvironment]);
            if ($deployment) {
                $errors[] = self::ERR_DUPLICATE_EB;
            }

        } elseif ($serverType == ServerEnumType::TYPE_EC2) {

            // EC2 did not change, skip dupe check
            if ($deployment->getEc2Pool() == $ec2Pool) {
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

        if ($server->getType() == ServerEnumType::TYPE_RSYNC) {
            $deployment = $this->deploymentRepo->findOneBy(['server' => $server, 'path' => $path]);
            if ($deployment) {
                $errors[] = self::ERR_DUPLICATE_RSYNC;
            }

        } elseif ($server->getType() == ServerEnumType::TYPE_EB) {
            $this->validateEbEnvironment($ebEnvironment);
            $deployment = $this->deploymentRepo->findOneBy(['ebEnvironment' => $ebEnvironment]);
            if ($deployment) {
                $errors[] = self::ERR_DUPLICATE_EB;
            }

        } elseif ($server->getType() == ServerEnumType::TYPE_EC2) {
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
     * @param int $repositoryId
     * @param int $serverId
     * @param string $url
     *
     * @return bool
     */
    private function validateRequired($repositoryId, $serverId, $url)
    {
        $errors = [];

        if (!$repositoryId) {
            $errors[] = sprintf(self::ERR_REQUIRED, 'Repository');
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
     * @param Repository $repo
     *
     * @return bool
     */
    private function validateEbConfigRequired(Repository $repo)
    {
        $errors = [];

        if (!$repo->getEbName()) {
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
