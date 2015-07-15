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
use QL\Hal\Core\JobIdGenerator;
use QL\Hal\Service\PermissionService;
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
    private $environmentRepo;
    private $buildRepo;

    /**
     * @type GitHubService
     */
    private $github;

    /**
     * @type PermissionService
     */
    private $permissions;

    /**
     * @type JobIdGenerator
     */
    private $unique;

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
     * @param PermissionService $permissions
     * @param JobIdGenerator $unique
     * @param User $currentUser
     */
    public function __construct(
        EntityManagerInterface $em,
        GitHubService $github,
        PermissionService $permissions,
        JobIdGenerator $unique,
        User $currentUser
    ) {
        $this->buildRepo = $em->getRepository(Build::CLASS);
        $this->environmentRepo = $em->getRepository(Environment::CLASS);

        $this->github = $github;
        $this->permissions = $permissions;
        $this->unique = $unique;

        $this->currentUser = $currentUser;

        $this->errors = [];
    }

    /**
     * @param Application $application
     * @param string $environmentID
     * @param string $gitReference
     * @param string $gitSearch
     *
     * @return Build|null
     */
    public function isValid(Application $application, $environmentID, $gitReference, $gitSearch)
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

        if (!$this->sanityCheck($environmentID, $reference)) {
            return null;
        }

        // no env
        if (!$env = $this->environmentRepo->find($environmentID)) {

            # attempt search by name
            if (!$env = $this->environmentRepo->findOneBy(['name' => $environmentID])) {
                $this->errors[] = self::ERR_NO_ENV;
            }
        }

        if ($this->errors) return;

        // no permission
        if (!$this->permissions->canUserBuild($this->currentUser, $application)) {
            $this->errors[] = self::ERR_NO_PERMISSION;
        }

        if ($this->errors) return;

        if (!$ref = $this->github->resolve($application->githubOwner(), $application->githubRepo(), $reference)) {
            $this->errors[] = self::ERR_UNKNOWN_REF;
        }

        if ($this->errors) return;

        list($reference, $commit) = $ref;
        if ($reference === 'commit') {
            $reference = $commit;
        }

        $id = $this->unique->generateBuildId();
        $build = (new Build($id))
            ->withStatus('Waiting')
            ->withBranch($reference)
            ->withCommit($commit)

            ->withUser($this->currentUser)
            ->withApplication($application)
            ->withEnvironment($env);

        $this->dupeCatcher($build);

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
     * @param string $environmentID
     * @param string $gitReference
     *
     * @return bool
     */
    private function sanityCheck($environmentID, $gitReference)
    {
        if (!$environmentID) {
            $this->errors[] = self::ERR_NO_ENV;
            return false;
        }

        if (!$gitReference) {
            $this->errors[] = self::ERR_UNKNOWN_REF;
            return false;
        }

        return true;
    }

    /**
     * @param Build $build
     *
     * @return null
     */
    private function dupeCatcher(Build $build)
    {
        $dupe = $this->buildRepo->find($build->id());
        if ($dupe) {
            $id = $this->unique->generateBuildId();
            $build->withId($id);
            $this->dupeCatcher($build);
        }
    }
}
