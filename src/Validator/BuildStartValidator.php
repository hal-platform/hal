<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Validator;

use QL\Hal\Core\Entity\Repository\EnvironmentRepository;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\User;
use QL\Hal\Services\GithubService;
use QL\Hal\Services\PermissionsService;

class BuildStartValidator
{
    const ERR_NO_REPO = 'Repository is required.';
    const ERR_NO_ENV = 'Environment is required.';
    const ERR_NO_PERMISSION = 'You do not have permission to create a build for this repository.';
    const ERR_UNKNOWN_REF = 'You must select a valid git reference.';

    /**
     * @type RepositoryRepository
     */
    private $repoRepo;

    /**
     * @type EnvironmentRepository
     */
    private $envRepo;

    /**
     * @type GithubService
     */
    private $github;

    /**
     * @type PermissionsService
     */
    private $permissions;

    /**
     * @type User
     */
    private $currentUser;

    /**
     * @type array
     */
    private $errors;

    /**
     * @param RepositoryRepository $repoRepo
     * @param EnvironmentRepository $envRepo
     * @param GithubService $github
     * @param PermissionsService $permissions
     * @param User $currentUser
     */
    public function __construct(
        RepositoryRepository $repoRepo,
        EnvironmentRepository $envRepo,
        GithubService $github,
        PermissionsService $permissions,
        User $currentUser
    ) {
        $this->repoRepo = $repoRepo;
        $this->envRepo = $envRepo;

        $this->github = $github;
        $this->permissions = $permissions;
        $this->currentUser = $currentUser;

        $this->errors = [];
    }

    /**
     * @param string $repositoryId
     * @param string $environmentId
     * @param string $gitReference
     *
     * @return Build|null
     */
    public function isValid($repositoryId, $environmentId, $gitReference)
    {
        $this->errors = [];

        if (!$this->sanityCheck($repositoryId, $environmentId, $gitReference)) {
            return null;
        }

        // no repo
        if (!$repo = $this->repoRepo->findOneBy(['id' => $repositoryId])) {
            $this->errors[] = self::ERR_NO_REPO;
            return null;
        }

        // no env
        if (!$repo = $this->envRepo->findOneBy(['id' => $environmentId])) {
            $this->errors[] = self::ERR_NO_ENV;
            return null;
        }

        // no permission
        if (!$this->permissions->allowBuild($this->currentUser, $repo->getKey())) {
            $this->errors[] = self::ERR_NO_PERMISSION;
            return null;
        }

        if (!$ref = $this->github->resolve($repo->getGithubUser(), $repo->getGithubRepo(), $gitReference)) {
            $this->errors[] = self::ERR_UNKNOWN_REF;
            return null;
        }

        list($reference, $commit) = $ref;

        $build = new Build;

        $build->setStatus('Waiting');
        $build->setBranch($reference);
        $build->setCommit($commit);

        $build->setUser($this->currentUser);
        $build->setRepository($repo);
        $build->setEnvironment($env);

        // Still needs ID assigned
        return $build;
    }

    /**
     * @return array
     */
    public function errors()
    {
        return $this->errors;
    }

    /**
     * @param string $repositoryId
     * @param string $environmentId
     * @param string $gitReference
     *
     * @return bool
     */
    private function sanityCheck($repositoryId, $environmentId, $gitReference)
    {
        if (!$repositoryId) {
            $this->errors[] = self::ERR_NO_REPO;
            return false;
        }

        if (!$environmentId) {
            $this->errors[] = self::ERR_NO_ENV;
            return false;
        }

        if (!$gitReference) {
            $this->errors[] = self::ERR_UNKNOWN_REF;
            return false;
        }

        return true;
    }
}
