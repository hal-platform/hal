<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Service;

use Github\Api\GitData\References as ReferencesAPI;
use Github\Api\PullRequest as PullRequestAPI;
use Github\Api\Repo as RepoAPI;
use Github\Api\Repository\Commits as RepoCommitsAPI;
use Github\Api\User as UserAPI;
use Github\Client;
use Github\Exception\RuntimeException;
use Github\ResultPager;
use Hal\UI\VersionControl\GitHub\GitHubResolver;
use Hal\UI\VersionControl\GitHub\GitHubURLBuilder;

/**
 * Combine all individual github api services into a giant convenience service.
 */
class GitHubService
{
    /**
     * @var GitHubResolver
     */
    public $resolver;

    /**
     * @var GitHubURLBuilder
     */
    public $urlBuilder;

    /**
     * @var ResultPager
     */
    private $pager;

    /**
     * @var RepoAPI
     */
    public $repoAPI;

    /**
     * @var RepoCommitsAPI
     */
    private $repoCommitsAPI;

    /**
     * @var ReferencesAPI
     */
    private $gitReferencesAPI;

    /**
     * @var PullRequestAPI
     */
    private $pullRequestAPI;

    /**
     * @var UserAPI
     */
    private $userAPI;

    /**
     * @param Client $client
     * @param GitHubResolver $resolver
     * @param GitHubURLBuilder $builder
     * @param ResultPager $pager
     */
    public function __construct(Client $client, GitHubResolver $resolver, GitHubURLBuilder $builder, ResultPager $pager)
    {
        $this->resolver = $resolver;
        $this->urlBuilder = $builder;
        $this->pager = $pager;

        $this->gitReferencesAPI = $client->api('git_data')->references();
        $this->pullRequestAPI = $client->api('pull_request');

        $this->repoAPI = $client->api('repo');
        $this->repoCommitsAPI = $client->api('repo')->commits();

        $this->userAPI = $client->api('user');
    }

    /**
     * @return GitHubResolver
     */
    public function resolver(): GitHubResolver
    {
        return $this->resolver;
    }

    /**
     * @return GitHubURLBuilder
     */
    public function url(): GitHubURLBuilder
    {
        return $this->urlBuilder;
    }

    /**
     * Get the reference data for branches for a repository.
     *
     * @param string $user
     * @param string $repo
     *
     * @return array
     */
    public function branches($user, $repo): array
    {
        try {
            $refs = $this->pager->fetchAll($this->gitReferencesAPI, 'branches', [$user, $repo]);
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
     * @param bool $getAll
     *
     * @return array
     */
    public function closedPullRequests($user, $repo, $getAll = false): array
    {
        try {
            if ($getAll) {
                $pulls = $this->pager->fetchAll($this->pullRequestAPI, 'all', [$user, $repo, ['state' => 'closed']]);
            } else {
                $pulls = $this->pullRequestAPI->all($user, $repo, ['state' => 'closed']);
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
     *
     * @return array
     */
    public function openPullRequests($user, $repo): array
    {
        try {
            $pulls = $this->pager->fetchAll($this->pullRequestAPI, 'all', [$user, $repo]);
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
     *
     * @return array|null
     */
    public function pullRequest($user, $repo, $number): ?array
    {
        try {
            $pull = $this->pullRequestAPI->show($user, $repo, $number);
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
     *
     * @return array|null
     */
    public function repository($user, $repo): ?array
    {
        try {
            $repository = $this->repoAPI->show($user, $repo);
        } catch (RuntimeException $e) {
            $repository = null;
        }

        return $repository;
    }

    /**
     * Get the reference data for tags for a repository.
     *
     * @param string $user
     * @param string $repo
     *
     * @return array
     */
    public function tags($user, $repo): array
    {
        try {
            $refs = $this->pager->fetchAll($this->gitReferencesAPI, 'tags', [$user, $repo]);
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
    public function user($user): ?array
    {
        try {
            $user = $this->userAPI->show($user);
        } catch (RuntimeException $e) {
            $user = null;
        }

        return $user;
    }

    /**
     * Compare two git commits
     *
     * @param string $user
     * @param string $repo
     * @param string $base
     * @param string $head
     *
     * @return array|string|null
     */
    public function diff($user, $repo, $base, $head)
    {
        try {
            return $this->repoCommitsAPI->compare($user, $repo, $base, $head);
        } catch (RuntimeException $e) {
            return null;
        }
    }
}
