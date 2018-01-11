<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\VersionControl\GitHub;

use Github\Client;
use Github\Api\GitData\Commits as CommitsAPI;
use Github\Api\GitData\References as ReferencesAPI;
use Github\Api\PullRequest as PullRequestAPI;
use Github\Exception\RuntimeException;
use Http\Client\Exception\RequestException;

class GitHubResolver
{
    /**
     * Git Reference Patterns
     */
    private const REGEX_TAG = '#^tag/([[:print:]]+)$#';
    private const REGEX_PULL = '#^pull/([\d]+)$#';
    public const REGEX_COMMIT = '#^[0-9a-f]{40}$#';

    /**
     * @var ReferencesAPI
     */
    private $gitReferencesAPI;

    /**
     * @var CommitsAPI
     */
    private $gitCommitsAPI;

    /**
     * @var PullRequestAPI
     */
    private $pullRequestAPI;

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->gitReferencesAPI = $client->api('git_data')->references();
        $this->gitCommitsAPI = $client->api('git_data')->commits();
        $this->pullRequestAPI = $client->api('pull_request');
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
     *
     * @return array|null
     */
    public function resolve($user, $repo, $reference): ?array
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
     * Resolve a git reference type in the following format.
     *
     * Tag: tag/(tag name)
     * Pull: pull/(pull request number)
     * Commit: (commit hash){40}
     * Branch: (branch name)
     *
     * Will return an array of ['type', $ref] or fallback to ['branch', $ref]
     *
     * @param string $reference
     *
     * @return array
     */
    public function resolveRefType($reference): ?array
    {
        if ($tag = $this->parseRefAsTag($reference)) {
            return ['tag', $tag];
        }

        if ($pull = $this->parseRefAsPull($reference)) {
            return ['pull', $pull];
        }

        if ($commit = $this->parseRefAsCommit($reference)) {
            return ['commit', $commit];
        }

        return ['branch', $reference];
    }

    /**
     * Parse a git reference as a tag, return null on failure.
     *
     * @param string $reference
     *
     * @return string|null
     */
    public function parseRefAsTag($reference): ?string
    {
        if (preg_match(self::REGEX_TAG, $reference, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }

    /**
     * Parse a git reference as a pull request, return null on failure.
     *
     * @param string $reference
     *
     * @return string|null
     */
    public function parseRefAsPull($reference): ?string
    {
        if (preg_match(self::REGEX_PULL, $reference, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }

    /**
     * Parse a git reference as a commit, return null on failure.
     *
     * @param string $reference
     *
     * @return string|null
     */
    public function parseRefAsCommit($reference): ?string
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
     *
     * @return null|string
     */
    private function resolveTag($user, $repo, $reference)
    {
        if (!$tag = $this->parseRefAsTag($reference)) {
            return null;
        }

        $params = [$user, $repo, sprintf('tags/%s', $tag)];

        $data = $this->callGitHub([$this->gitReferencesAPI, 'show'], $params);
        return $data['object']['sha'] ?? null;
    }

    /**
     * Resolve a pull request reference. Returns the commit sha or null on failure.
     *
     * @param string $user
     * @param string $repo
     * @param string $reference
     *
     * @return string|null
     */
    private function resolvePull($user, $repo, $reference)
    {
        if (!$pull = $this->parseRefAsPull($reference)) {
            return null;
        }

        $params = [$user, $repo, $pull];

        $data = $this->callGitHub([$this->pullRequestAPI, 'show'], $params);
        return $data['head']['sha'] ?? null;
    }

    /**
     * Resolve a commit reference. Returns the commit sha or null on failure.
     *
     * @param string $user
     * @param string $repo
     * @param string $reference
     *
     * @return string|null
     */
    private function resolveCommit($user, $repo, $reference)
    {
        if (!$commit = $this->parseRefAsCommit($reference)) {
            return null;
        }

        $params = [$user, $repo, $commit];

        $data = $this->callGitHub([$this->gitCommitsAPI, 'show'], $params);
        return $data['sha'] ?? null;
    }

    /**
     * Resolve a branch reference. Returns the head commit sha or null on failure.
     *
     * @param string $user
     * @param string $repo
     * @param string $branch
     *
     * @return string|null
     */
    private function resolveBranch($user, $repo, $branch)
    {
        $params = [$user, $repo, sprintf('heads/%s', $branch)];

        $data = $this->callGitHub([$this->gitReferencesAPI, 'show'], $params);
        return $data['object']['sha'] ?? null;
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
