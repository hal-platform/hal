<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Twig;

use MCP\Cache\CachingTrait;
use QL\Hal\Service\GitHubService;
use QL\Hal\Github\GitHubURLBuilder;
use Twig_Extension;
use Twig_SimpleFilter;
use Twig_SimpleFunction;
use Exception;

class GitHubExtension extends Twig_Extension
{
    use CachingTrait;

    const NAME = 'github_permissions';

    /**
     * @type GitHubService
     */
    private $github;

    /**
     * @type GitHubURLBuilder
     */
    private $urlBuilder;

    /**
     * @param GitHubService $github
     * @param GitHubURLBuilder $urlBuilder
     */
    public function __construct(GitHubService $github, GitHubURLBuilder $urlBuilder)
    {
        $this->github = $github;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new Twig_SimpleFunction('githubRepoUrl', [$this->urlBuilder, 'githubRepoURL']),
            new Twig_SimpleFunction('githubCommitUrl', [$this->urlBuilder, 'githubCommitURL']),
            new Twig_SimpleFunction('githubBranchUrl', [$this->urlBuilder, 'githubBranchURL']),
            new Twig_SimpleFunction('githubPullRequestUrl', [$this->urlBuilder, 'githubPullRequestURL']),
            new Twig_SimpleFunction('githubReferenceUrl', [$this->urlBuilder, 'githubReferenceURL']),
            new Twig_SimpleFunction('githubReleaseUrl', [$this->urlBuilder, 'githubReleaseURL']),

            new Twig_SimpleFunction('githubCommitIsCurrent', [$this, 'commitIsCurrent'])
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new Twig_SimpleFilter('gitref', [$this, 'resolveGitReference']),
            new Twig_SimpleFilter('commit', [$this, 'formatGitCommit'])
        ];
    }

    /**
     * Check if a commit hash is the most recent for a given Github user, repo, and reference
     *
     * @param string $user
     * @param string $repo
     * @param string $reference
     * @param string $commit
     *
     * @return bool
     */
    public function commitIsCurrent($user, $repo, $reference, $commit)
    {
        // cache ref comparisons in memory
        $key = md5($user . $repo . $reference . $commit);

        if (null !== ($isCurrent = $this->getFromCache($key))) {
            return $isCurrent;
        }

        $latest = $this->resolveRefToLatestCommit($user, $repo, $reference);
        $isCurrent = ($latest == $commit) ? true : false;

        $this->setToCache($key, $isCurrent, 120);
        return $isCurrent;
    }

    /**
     * Format a git commit hash for output
     *
     * @param $reference
     * @return string
     */
    public function formatGitCommit($reference)
    {
        return substr($reference, 0, 7);
    }

    /**
     * Format an arbitrary git reference for display
     *
     * @param $reference
     *
     * @return array
     */
    public function resolveGitReference($reference)
    {
        if ($tag = $this->github->parseRefAsTag($reference)) {
            return ['tag', $tag];
        }

        if ($pull = $this->github->parseRefAsPull($reference)) {
            return ['pull', $pull];
        }

        if ($commit = $this->github->parseRefAsCommit($reference)) {
            return ['commit', $commit];
        }

        return ['branch', $reference];
    }

    /**
     * Check if a commit hash is the most recent for a given Github user, repo, and reference
     *
     * @param string $user
     * @param string $repo
     * @param string $reference
     *
     * @return string|null
     */
    private function resolveRefToLatestCommit($user, $repo, $reference)
    {
        $key = md5($user . $repo . $reference);

        if (null !== ($latest = $this->getFromCache($key))) {
            return $latest;
        }

        try {
            $resolve = $this->github->resolve($user, $repo, $reference);
            $latest = (is_array($resolve)) ? $resolve[1] : null;

        // Fuck off weird errors
        } catch (Exception $ex) {
            $latest = null;
        }

        $this->setToCache($key, $latest, 15);
        return $latest;
    }
}
