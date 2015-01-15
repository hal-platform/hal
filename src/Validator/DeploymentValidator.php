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

class DeploymentValidator
{
    const TYPE_RSYNC = 'rsync';
    const TYPE_EBS = 'elasticbeanstalk';

    const ERR_REQUIRED = '"%s" is required.';

    const ERR_INVALID_PATH = 'File path is invalid.';
    const ERR_INVALID_URL = 'URL is invalid.';
    const ERR_INVALID_REPO = 'Repository is invalid.';
    const ERR_INVALID_SERVER = 'Server is invalid.';
    const ERR_INVALID_EBS_ENVIRONMENT = 'EBS Environment is invalid.';

    const ERR_DUPLICATE_RSYNC = 'A deployment already exists for this server and file path.';
    const ERR_DUPLICATE_EBS = 'A deployment already exists for this EBS environment ID.';
    const ERR_INVALID_EBS_PROJECT = 'EBS Application Name must be configured before adding EBS Deployments.';

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
     * @param string $ebsEnvironment
     * @param string $url
     *
     * @return Deployment|null
     */
    public function isValid($repositoryId, $serverId, $path, $ebsEnvironment, $url)
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

        if ($server->getType() == self::TYPE_RSYNC) {
            $this->validatePath($path);

        } elseif ($server->getType() == self::TYPE_EBS) {
            $this->validateEbsEnvironment($ebsEnvironment);
            $this->validateEbsConfigRequired($repo);
        }

        // stop validation if errors
        if ($this->errors) {
            return null;
        }

        // check dupes
        $this->validateNewDuplicate($server, $ebsEnvironment, $path);

        // stop validation if errors
        if ($this->errors) {
            return null;
        }

        // Wipe path for EBS servers
        // Wipe ebs for RSYNC server
        $serverType = $server->getType();
        if ($serverType === self::TYPE_RSYNC) {
            $ebsEnvironment = null;
        } else if ($serverType === self::TYPE_EBS) {
            $path = null;
        }

        $deployment = new Deployment;
        $deployment->setRepository($repo);
        $deployment->setServer($server);

        $deployment->setPath($path);
        $deployment->setEbsEnvironment($ebsEnvironment);
        $deployment->setUrl(HttpUrl::create($url));

        return $deployment;
    }

    /**
     * @param Deployment $deployment
     * @param string $path
     * @param string $ebsEnvironment
     * @param string $url
     *
     * @return Deployment|null
     */
    public function isEditValid(Deployment $deployment, $path, $ebsEnvironment, $url)
    {
        $this->errors = [];

        $serverType = $deployment->getServer()->getType();

        $this->validateUrl($url);

        if ($serverType == self::TYPE_RSYNC) {
            $this->validatePath($path);

        } elseif ($serverType == self::TYPE_EBS) {
            $this->validateEbsEnvironment($ebsEnvironment);
            $this->validateEbsConfigRequired($deployment->getRepository());
        }

        // check dupes
        $this->validateCurrentDuplicate($deployment, $ebsEnvironment, $path);

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
     * @param string $ebsEnvironment
     * @param string $path
     *
     * @return bool
     */
    private function validateCurrentDuplicate(Deployment $deployment, $ebsEnvironment, $path)
    {
        $errors = [];

        $server = $deployment->getServer();
        $serverType = $server->getType();

        if ($serverType == self::TYPE_RSYNC) {

            // rsync path did not change, skip dupe check
            if ($deployment->getPath() == $path) {
                goto SKIP_VALIDATION;
            }

            $deployment = $this->deploymentRepo->findOneBy(['server' => $server, 'path' => $path]);
            if ($deployment) {
                $errors[] = self::ERR_DUPLICATE_RSYNC;
            }

        } elseif ($serverType == self::TYPE_EBS) {

            // ebs did not change, skip dupe check
            if ($deployment->getEbsEnvironment() == $ebsEnvironment) {
                goto SKIP_VALIDATION;
            }

            $deployment = $this->deploymentRepo->findOneBy(['ebsEnvironment' => $ebsEnvironment]);
            if ($deployment) {
                $errors[] = self::ERR_DUPLICATE_EBS;
            }
        }

        SKIP_VALIDATION:

        $this->errors = array_merge($this->errors, $errors);
        return count($errors) === 0;
    }


    /**
     * @param Server $server
     * @param string $ebsEnvironment
     * @param string $path
     *
     * @return bool
     */
    private function validateNewDuplicate(Server $server, $ebsEnvironment, $path)
    {
        $errors = [];

        if ($server->getType() == self::TYPE_RSYNC) {
            $deployment = $this->deploymentRepo->findOneBy(['server' => $server, 'path' => $path]);
            if ($deployment) {
                $errors[] = self::ERR_DUPLICATE_RSYNC;
            }

        } elseif ($server->getType() == self::TYPE_EBS) {
            $this->validateEbsEnvironment($ebsEnvironment);
            $deployment = $this->deploymentRepo->findOneBy(['ebsEnvironment' => $ebsEnvironment]);
            if ($deployment) {
                $errors[] = self::ERR_DUPLICATE_EBS;
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
     * @param string $ebsEnvironment
     *
     * @return bool
     */
    private function validateEbsEnvironment($ebsEnvironment)
    {
        $errors = [];

        if (!$ebsEnvironment) {
            $errors[] = sprintf(self::ERR_REQUIRED, 'EBS Environment');
        }

        if (preg_match('#[\t\n]+#', $ebsEnvironment) === 1) {
            $errors[] = self::ERR_INVALID_EBS_ENVIRONMENT;
        }

        $this->errors = array_merge($this->errors, $errors);
        return count($errors) === 0;
    }

    /**
     * @param Repository $repo
     *
     * @return bool
     */
    private function validateEbsConfigRequired(Repository $repo)
    {
        $errors = [];

        if (!$repo->getEbsName()) {
            $errors[] = self::ERR_INVALID_EBS_PROJECT;
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
