<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Service;

use Github\Api\GitData\Commits as CommitApi;
use Github\Api\GitData\References as ReferenceApi;
use Github\Api\Organization\Members as OrganizationMembersApi;
use Github\Api\PullRequest as PullRequestApi;
use Github\Api\Repo as RepoApi;
use Github\Api\Repository\Commits as RepoCommitApi;
use Github\Api\User as UserApi;
use Github\Exception\RuntimeException;
use Github\ResultPager;

/**
 * Combine all individual github api services into a giant convenience service.
 */
class GitHubService
{
    /**
     * Git Reference Patterns
     */
    const REGEX_TAG = '#^tag/([[:print:]]+)$#';
    const REGEX_PULL = '#^pull/([\d]+)$#';
    const REGEX_COMMIT = '#^[0-9a-f]{40}$#';

    /**
     * @var RepoApi
     */
    public $repoApi;

    /**
     * @var RepoCommitApi
     */
    private $repoCommitApi;

    /**
     * @var ReferenceApi
     */
    private $gitReferenceApi;

    /**
     * @var CommitApi
     */
    private $gitCommitApi;

    /**
     * @var PullRequestApi
     */
    private $pullRequestApi;

    /**
     * @var UserApi
     */
    private $userApi;

    /**
     * @var OrganizationMembersApi
     */
    private $orgMembersApi;

    /**
     * @var ResultPager
     */
    private $pager;

    /**
     * @param RepoApi $repoApi
     * @param RepoCommitApi $repoCommitApi
     *
     * @param ReferenceApi $gitReferenceApi
     * @param CommitApi $gitCommitApi
     *
     * @param PullRequestApi $pullRequestApi
     *
     * @param UserApi $userApi
     * @param OrganizationMembersApi $orgMembersApi
     *
     * @param ResultPager $pager
     */
    public function __construct(
        RepoApi $repoApi,
        RepoCommitApi $repoCommitApi,
        //
        ReferenceApi $gitReferenceApi,
        CommitApi $gitCommitApi,
        //
        PullRequestApi $pullRequestApi,
        //
        UserApi $userApi,
        OrganizationMembersApi $orgMembersApi,
        //
        ResultPager $pager
    ) {
        $this->repoApi = $repoApi;
        $this->repoCommitApi = $repoCommitApi;

        $this->gitReferenceApi = $gitReferenceApi;
        $this->gitCommitApi = $gitCommitApi;

        $this->pullRequestApi = $pullRequestApi;

        $this->userApi = $userApi;
        $this->orgMembersApi = $orgMembersApi;

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
            $refs = $this->pager->fetchAll($this->gitReferenceApi, 'branches', [$user, $repo]);
        } catch (RuntimeException $e) {
            $refs = [];
        }

        array_walk($refs, function (&$data) {
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
                $pulls = $this->pager->fetchAll($this->pullRequestApi, 'all', [$user, $repo, ['state' => 'closed']]);
            } else {
                $pulls = $this->pullRequestApi->all($user, $repo, ['state' => 'closed']);
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
            $pulls = $this->pager->fetchAll($this->pullRequestApi, 'all', [$user, $repo]);
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
            $pull = $this->pullRequestApi->show($user, $repo, $number);
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
            $refs = $this->pager->fetchAll($this->gitReferenceApi, 'tags', [$user, $repo]);
        } catch (RuntimeException $e) {
            $refs = [];
        }

        array_walk($refs, function (&$data) {
            $data['name'] = str_replace('refs/tags/', '', $data['ref']);
        });

        return $refs;
    }

    /**
     * Get the extended metadata for a github user
     *
     * @param string $user
     *
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
     * @param string $organization
     * @param string $user
     *
     * @return boolean
     */
    public function isUserOrganizationMember($organization, $user)
    {
        try {
            // A successful response returns 'null'
            $this->orgMembersApi->check($organization, $user);
        } catch (RuntimeException $e) {
            return false;
        }

        return true;
    }

    /**
     * Compare two git commits
     *
     * @param $user
     * @param $repo
     * @param $base
     * @param $head
     *
     * @return array|string
     */
    public function diff($user, $repo, $base, $head)
    {
        return $this->repoCommitApi->compare($user, $repo, $base, $head);
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
        if (strlen($reference) === 0) {
            return null;
        }

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
     * Parse a git reference as a tag, return null on failure.
     *
     * @param $reference
     * @return string|null
     */
    public function parseRefAsTag($reference)
    {
        if (preg_match(self::REGEX_TAG, $reference, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }

    /**
     * Parse a git reference as a pull request, return null on failure.
     *
     * @param $reference
     * @return string|null
     */
    public function parseRefAsPull($reference)
    {
        if (preg_match(self::REGEX_PULL, $reference, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }

    /**
     * Parse a git reference as a commit, return null on failure.
     *
     * @param $reference
     * @return string|null
     */
    public function parseRefAsCommit($reference)
    {
        if (preg_match(self::REGEX_COMMIT, $reference, $matches) !== 1) {
            return null;
        }

        return $matches[0];
    }

    /**
     * Resolve a tag reference. Returns the commit sha or null on failure.
     *
     * @param string $user
     * @param string $repo
     * @param string $reference
     * @return null|string
     */
    private function resolveTag($user, $repo, $reference)
    {
        if (!$tag = $this->parseRefAsTag($reference)) {
            return null;
        }

        try {
            $result = $this->gitReferenceApi->show($user, $repo, sprintf('tags/%s', $tag));
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
     * @param string $reference
     * @return null|string
     */
    private function resolvePull($user, $repo, $reference)
    {
        if (!$pull = $this->parseRefAsPull($reference)) {
            return null;
        }

        try {
            $result = $this->pullRequestApi->show($user, $repo, $pull);
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
     * @param string $reference
     * @return null|string
     */
    private function resolveCommit($user, $repo, $reference)
    {
        if (!$commit = $this->parseRefAsCommit($reference)) {
            return null;
        }

        try {
            $result = $this->gitCommitApi->show($user, $repo, $commit);
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
            $result = $this->gitReferenceApi->show($user, $repo, sprintf('heads/%s', $branch));
        } catch (RuntimeException $e) {
            return null;
        }

        return $result['object']['sha'];
    }
}
