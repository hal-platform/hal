<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Validator;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\User;
use QL\Hal\Service\PermissionsService;
use QL\Hal\Service\GitHubService;

class BuildStartValidator
{
    const ERR_NO_APPLICATION = 'Application is required.';
    const ERR_NO_ENV = 'Environment is required.';
    const ERR_NO_PERMISSION = 'You do not have permission to create a build for this application.';
    const ERR_UNKNOWN_REF = 'You must select a valid git reference.';

    /**
     * Valid entries:
     *
     * - pr#500
     * - pr 500
     * - pull 500
     * - pull #500
     * - pull request 500
     * - pull request #500
     */
    const REGEX_PULL = '/^(?:pull|pr|pull request)(?: )?(?:#)?([\d]+)$/i';

    /**
     * @type EntityRepository
     */
    private $applicationRepo;
    private $envRepo;

    /**
     * @type GitHubService
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
     * @param EntityManagerInterface $em
     * @param GitHubService $github
     * @param PermissionsService $permissions
     * @param User $currentUser
     */
    public function __construct(
        EntityManagerInterface $em,
        GitHubService $github,
        PermissionsService $permissions,
        User $currentUser
    ) {
        $this->applicationRepo = $em->getRepository(Application::CLASS);
        $this->envRepo = $em->getRepository(Environment::CLASS);

        $this->github = $github;
        $this->permissions = $permissions;
        $this->currentUser = $currentUser;

        $this->errors = [];
    }

    /**
     * @param string $applicationId
     * @param string $environmentId
     * @param string $gitReference
     * @param string $gitSearch
     *
     * @return Build|null
     */
    public function isValid($applicationId, $environmentId, $gitReference, $gitSearch)
    {
        $this->errors = [];

        // gitref,reference
            // pull/*       - pull request
            // tag/*        - tag
            // *            - branch
        // search
            // [a-f]{40}    - commit
            // pr \d        - pull request, See validator for full pull request regex

        // Git reference cascades through these options:
        // 1. "reference" - a radio option selected by user
        // 2. "search" - a search query provided by user
        $reference = $this->parseSubmittedRef($gitReference, $gitSearch);

        if (!$this->sanityCheck($applicationId, $environmentId, $reference)) {
            return null;
        }

        // no repo
        if (!$application = $this->applicationRepo->find($applicationId)) {
            $this->errors[] = self::ERR_NO_REPO;
            return null;
        }

        // no env
        if (!$env = $this->envRepo->find($environmentId)) {
            $this->errors[] = self::ERR_NO_ENV;
            return null;
        }

        // no permission
        if (!$this->permissions->canUserBuild($this->currentUser, $application)) {
            $this->errors[] = self::ERR_NO_PERMISSION;
            return null;
        }

        if (!$ref = $this->github->resolve($application->githubOwner(), $application->githubRepo(), $reference)) {
            $this->errors[] = self::ERR_UNKNOWN_REF;
            return null;
        }

        list($reference, $commit) = $ref;
        if ($reference === 'commit') {
            $reference = $commit;
        }

        $build = (new Build)
            ->withStatus('Waiting')
            ->withBranch($reference)
            ->withCommit($commit)

            ->withUser($this->currentUser)
            ->withApplication($application)
            ->withEnvironment($env);

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
     * @param string $selectedOption
     * @param string $search
     *
     * @return string
     */
    private function parseSubmittedRef($selectedOption, $search)
    {
        // if user selected a radio, use it
        if ($selectedOption) {
            return $selectedOption;
        }

        // search query is commit sha
        if (preg_match(GitHubService::REGEX_COMMIT, $search) === 1) {
            return $search;
        }

        // search query is pull request
        if (preg_match(self::REGEX_PULL, $search, $matches) === 1) {
            return sprintf('pull/%d', array_pop($matches));
        }

        return $search;
    }

    /**
     * @param string $applicationId
     * @param string $environmentId
     * @param string $gitReference
     *
     * @return bool
     */
    private function sanityCheck($applicationId, $environmentId, $gitReference)
    {
        if (!$applicationId) {
            $this->errors[] = self::ERR_NO_APPLICATION;
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
