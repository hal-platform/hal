<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Services;

use Github\Api\GitData\Commits as CommitApi;
use Github\Api\GitData\References as ReferenceApi;
use Github\Api\PullRequest as PullRequestApi;

use Github\Api\Repo;
use Github\Exception\RuntimeException;
use Github\ResultPager;
use QL\Hal\GithubApi\HackUser;

/**
 * You know whats really annoying? Wrapping every api request in a try/catch.
 *
 * This helps abstract the awfulness of the github api library from the rest of Hal.
 */
class GithubService
{
    /**
     * Git Reference Patterns
     */
    const REGEX_TAG = '#^tag/([[:print:]]+)$#';
    const REGEX_PULL = '#^pull/([\d]+)$#';
    const REGEX_COMMIT = '#^[0-9a-f]{40}$#';

    /**
     * @var HackUser
     */
    private $userApi;

    /**
     * @var Repo
     */
    public $repoApi;

    /**
     * @var ReferenceApi
     */
    private $refApi;

    /**
     * @var PullRequestApi
     */
    private $pullApi;

    /**
     * @var CommitApi
     */
    private $commitApi;

    /**
     * @var ResultPager
     */
    private $pager;

    /**
     * @param HackUser $user
     * @param Repo $repo
     * @param ReferenceApi $ref
     * @param PullRequestApi $pull
     * @param CommitApi $commit
     * @param ResultPager $pager
     */
    public function __construct(HackUser $user, Repo $repo, ReferenceApi $ref, PullRequestApi $pull, CommitApi $commit, ResultPager $pager)
    {
        $this->userApi = $user;
        $this->repoApi = $repo;
        $this->refApi = $ref;
        $this->pullApi = $pull;
        $this->commitApi = $commit;
        $this->pager = $pager;
    }

    /**
     * Get the reference data for branches for a repository.
     *
     * @param string $user
     * @param string $repo
     * @return array
     */
    public function branches($user, $repo)
    {
        try {
            $refs = $this->pager->fetchAll($this->refApi, 'branches', [$user, $repo]);
        } catch (RuntimeException $e) {
            $refs = [];
        }

        array_walk($refs, function(&$data) {
            $data['name'] = str_replace('refs/heads/', '', $data['ref']);
        });

        return $refs;
    }

    /**
     * Get most recent closed pull requests for a repository.
     *
     * This only gets the most recent 30. If you need more than that, get all of them.
     *
     * It does not appear possible to get both open and closed pull requests from the same api call,
     * even though the api documentation specifies it is.
     *
     * @param string $user
     * @param string $repo
     * @param boolean $getAll
     * @return array
     */
    public function closedPullRequests($user, $repo, $getAll = false)
    {
        try {
            if ($getAll) {
                $pulls = $this->pager->fetchAll($this->pullApi, 'all', [$user, $repo, 'closed']);
            } else {
                $pulls = $this->pullApi->all($user, $repo, 'closed');
            }
        } catch (RuntimeException $e) {
            $pulls = [];
        }

        return $pulls;
    }

    /**
     * Get all open pull requests for a repository.
     *
     * It does not appear possible to get both open and closed pull requests from the same api call,
     * even though the api documentation specifies it is.
     *
     * @param string $user
     * @param string $repo
     * @return array
     */
    public function openPullRequests($user, $repo)
    {
        try {
            $pulls = $this->pager->fetchAll($this->pullApi, 'all', [$user, $repo]);
        } catch (RuntimeException $e) {
            $pulls = [];
        }

        return $pulls;
    }

    /**
     * Get metadata for a specific pull request.
     *
     * @param string $user
     * @param string $repo
     * @param string $number
     * @return array|null
     */
    public function pullRequest($user, $repo, $number)
    {
        try {
            $pull = $this->pullApi->show($user, $repo, $number);
        } catch (RuntimeException $e) {
            $pull = null;
        }

        return $pull;
    }

    /**
     * Get the extended metadata for a repository.
     *
     * @param string $user
     * @param string $repo
     * @return array|null
     */
    public function repository($user, $repo)
    {
        try {
            $repository = $this->repoApi->show($user, $repo);
        } catch (RuntimeException $e) {
            $repository = null;
        }

        return $repository;
    }

    /**
     * Get all repositories for a user.
     *
     * @param string $user
     * @return array
     */
    public function repositories($user)
    {
        try {
            $repositories = $this->pager->fetchAll($this->userApi, 'repositories', [$user]);
        } catch (RuntimeException $e) {
            $repositories = [];
        }

        return $repositories;
    }

    /**
     * Get the reference data for tags for a repository.
     *
     * @param string $user
     * @param string $repo
     * @return array
     */
    public function tags($user, $repo)
    {
        try {
            $refs = $this->pager->fetchAll($this->refApi, 'tags', [$user, $repo]);
        } catch (RuntimeException $e) {
            $refs = [];
        }

        array_walk($refs, function(&$data) {
            $data['name'] = str_replace('refs/tags/', '', $data['ref']);
        });

        return $refs;
    }

    /**
     * Get the extended metadata for a user.
     *
     * @param string $user
     * @return array|null
     */
    public function user($user)
    {
        try {
            $user = $this->userApi->show($user);
        } catch (RuntimeException $e) {
            $user = null;
        }

        return $user;
    }

    /**
     * Get the extended metadata for all github users.
     *
     * @return array
     */
    public function users()
    {
        try {
            $users = $this->pager->fetchAll($this->userApi, 'all');
        } catch (RuntimeException $e) {
            $users = [];
        }

        return $users;
    }

    /**
     * @param string $owner
     * @param string $repo
     * @param string $user
     * @return boolean
     */
    public function isUserCollaborator($owner, $repo, $user)
    {
        try {
            // A successful response returns 'null'
            $this->repoApi->collaborators()->check($owner, $repo, $user);
        } catch (RuntimeException $e) {
            return false;
        }

        return true;
    }

    /**
     * Resolve a git reference in the following format.
     *
     * Tag: tag/(tag name)
     * Pull: pull/(pull request number)
     * Commit: (commit hash){40}
     * Branch: (branch name)
     *
     * Will return an array of [reference, commit] or null on failure
     *
     * @param string $user
     * @param string $repo
     * @param string $reference
     * @return null|array
     */
    public function resolve($user, $repo, $reference)
    {
        //die(var_dump($user, $repo, $reference));

        if ($sha = $this->resolveTag($user, $repo, $reference)) {
            return [$reference, $sha];
        }

        if ($sha = $this->resolvePull($user, $repo, $reference)) {
            return [$reference, $sha];
        }

        if ($sha = $this->resolveCommit($user, $repo, $reference)) {
            return ['commit', $sha];
        }

        if ($sha = $this->resolveBranch($user, $repo, $reference)) {
            return [$reference, $sha];
        }

        return null;
    }

    /**
     * Resolve a tag reference. Returns the commit sha or null on failure.
     *
     * @param string $user
     * @param string $repo
     * @param string $tag
     * @return null|string
     */
    private function resolveTag($user, $repo, $tag)
    {
        if (preg_match(self::REGEX_TAG, $tag, $matches) !== 1) {
            return null;
        }

        try {
            $result = $this->refApi->show($user, $repo, sprintf('tags/%s', $matches[1]));
        } catch (RuntimeException $e) {
            return null;
        }

        return $result['object']['sha'];
    }

    /**
     * Resolve a pull request reference. Returns the commit sha or null on failure.
     *
     * @param string $user
     * @param string $repo
     * @param string $pull
     * @return null|string
     */
    private function resolvePull($user, $repo, $pull)
    {
        if (preg_match(self::REGEX_PULL, $pull, $matches) !== 1) {
            return null;
        }

        try {
            $result = $this->pullApi->show($user, $repo, $matches[1]);
        } catch (RuntimeException $e) {
            return null;
        }

        return $result['head']['sha'];
    }

    /**
     * Resolve a commit reference. Returns the commit sha or null on failure.
     *
     * @param string $user
     * @param string $repo
     * @param string $commit
     * @return null|string
     */
    private function resolveCommit($user, $repo, $commit)
    {
        if (preg_match(self::REGEX_COMMIT, $commit, $matches) !== 1) {
            return null;
        }

        try {
            $result = $this->commitApi->show($user, $repo, $matches[0]);
        } catch (RuntimeException $e) {
            return null;
        }

        return $result['sha'];
    }

    /**
     * Resolve a branch reference. Returns the head commit sha or null on failure.
     *
     * @param string $user
     * @param string $repo
     * @param string $branch
     * @return null|string
     */
    private function resolveBranch($user, $repo, $branch)
    {
        try {
            $result = $this->refApi->show($user, $repo, sprintf('heads/%s', $branch));
        } catch (RuntimeException $e) {
            return null;
        }

        return $result['object']['sha'];
    }
}
