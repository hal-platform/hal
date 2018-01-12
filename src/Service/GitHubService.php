<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
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
use Hal\UI\VersionControl\GitHub\RefSortingTrait;
use Http\Client\Exception\RequestException;

/**
 * Combine all individual github api services into a giant convenience service.
 */
class GitHubService
{
    use RefSortingTrait;

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

        $this->gitReferencesAPI = new ReferencesAPI($client);
        $this->pullRequestAPI = new PullRequestAPI($client);

        $this->repoAPI = new RepoAPI($client);
        $this->repoCommitsAPI = new RepoCommitsAPI($client);

        $this->userAPI = new UserAPI($client);
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
        $default = [];
        $params = [$this->gitReferencesAPI, 'branches', [$user, $repo]];

        $refs = $this->callGitHub([$this->pager, 'fetchAll'], $params, $default);

        array_walk($refs, function (&$data) {
            $data['name'] = str_replace('refs/heads/', '', $data['ref']);
        });

        usort($refs, $this->branchSorter());

        return $refs;
    }

    /**
     * Get most recent closed pull requests for a repository.
     *
     * It does not appear possible to get both open and closed pull requests from the same api call,
     * even though the api documentation specifies it is.
     *
     * @param string $user
     * @param string $repo
     *
     * @return array
     */
    public function closedPullRequests($user, $repo): array
    {
        $default = [];
        $params = [$user, $repo, ['state' => 'closed']];

        $refs = $this->callGitHub([$this->pullRequestAPI, 'all'], $params, $default);

        usort($refs, $this->prSorter($user));

        return $refs;
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
    public function openPullRequests($user, $repo, $halUser = null): array
    {
        $default = [];
        $params = [$this->pullRequestAPI, 'all', [$user, $repo]];

        $refs = $this->callGitHub([$this->pager, 'fetchAll'], $params, $default);

        usort($refs, $this->prSorter($halUser));

        return $refs;
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
        $params = [$user, $repo, $number];

        $refs = $this->callGitHub([$this->pullRequestAPI, 'show'], $params);

        return $refs;
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
        $params = [$user, $repo];

        $repository = $this->callGitHub([$this->repoAPI, 'show'], $params);

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
        $default = [];
        $params = [$this->gitReferencesAPI, 'tags', [$user, $repo]];

        $refs = $this->callGitHub([$this->pager, 'fetchAll'], $params, $default);

        array_walk($refs, function (&$data) {
            $data['name'] = str_replace('refs/tags/', '', $data['ref']);
        });

        usort($refs, $this->tagSorter());

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
        $params = [$user];

        return $this->callGitHub([$this->userAPI, 'show'], $params);
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
        $params = [$user, $repo, $base, $head];

        return $this->callGitHub([$this->repoCommitsAPI, 'compare'], $params);
    }

    /**
     * @param callable $api
     * @param array $params
     * @param mixed $default
     *
     * @return array|string|null
     */
    private function callGitHub(callable $api, array $params = [], $default = null)
    {
        try {
            $response = $api(...$params);

        } catch (RequestException $e) {
            $response = $default;

        } catch (RuntimeException $e) {
            $response = $default;
        }

        return $response;
    }
}
