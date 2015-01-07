<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Validator;

use MCP\DataType\HttpUrl;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Server;
use QL\Hal\Core\Entity\Repository\ServerRepository;
use QL\Hal\Core\Entity\Repository\DeploymentRepository;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;

class AddDeploymentValidator
{
    const ERR_REQUIRED = '"%s" is required.';

    const ERR_INVALID_PATH = 'File path is invalid.';
    const ERR_INVALID_URL = 'URL is invalid.';
    const ERR_INVALID_REPO = 'Repository is invalid.';
    const ERR_INVALID_SERVER = 'Server is invalid.';

    const ERR_DUPLICATE = 'A deployment already exists for this server and file path.';

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
     * @param string $path
     * @param string $url
     *
     * @return Deployment|null
     */
    public function isValid($repositoryId, $serverId, $path, $url)
    {
        $this->errors = [];

        $this->validateRequired($repositoryId, $serverId, $path, $url);

        // stop validation if errors
        if ($this->errors) {
            return null;
        }

        $this->validatePath($path);
        $this->validateUrl($url);

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

        $this->validateDuplicate($server, $path);

        // stop validation if errors
        if ($this->errors) {
            return null;
        }

        $deployment = new Deployment;
        $deployment->setRepository($repo);
        $deployment->setServer($server);
        $deployment->setPath($path);
        $deployment->setUrl(HttpUrl::create($url));

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
     * @param Server $server
     * @param string $path
     *
     * @return bool
     */
    private function validateDuplicate(Server $server, $path)
    {
        $errors = [];

        $deployment = $this->deploymentRepo->findOneBy(['server' => $server, 'path' => $path]);
        if ($deployment) {
            $errors[] = self::ERR_DUPLICATE;
        }

        $this->errors = array_merge($this->errors, $errors);
        return count($errors) === 0;
    }

    /**
     * @param int $repositoryId
     * @param int $serverId
     * @param string $path
     * @param string $url
     *
     * @return bool
     */
    private function validateRequired($repositoryId, $serverId, $path, $url)
    {
        $errors = [];

        if (!$repositoryId) {
            $errors[] = sprintf(self::ERR_REQUIRED, 'Repository');
        }

        if (!$serverId) {
            $errors[] = sprintf(self::ERR_REQUIRED, 'Server');
        }

        if (!$path) {
            $errors[] = sprintf(self::ERR_REQUIRED, 'Path');
        }

        if (!$url) {
            $errors[] = sprintf(self::ERR_REQUIRED, 'URL');
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
