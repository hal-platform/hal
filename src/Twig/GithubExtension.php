<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Twig;

use MCP\Cache\CachingTrait;
use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Service\GitHubService;
use Twig_Extension;
use Twig_SimpleFilter;
use Twig_SimpleFunction;
use Exception;

class GitHubExtension extends Twig_Extension
{
    use CachingTrait;

    const NAME = 'github_permissions';

    /**
     * @type UrlHelper
     */
    private $url;

    /**
     * @type GitHubService
     */
    private $github;

    /**
     * @param UrlHelper $url
     * @param GitHubService $github
     */
    public function __construct(UrlHelper $url, GitHubService $github)
    {
        $this->url = $url;
        $this->github = $github;
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
            new Twig_SimpleFunction('githubRepo', [$this->url, 'githubRepoUrl']),
            new Twig_SimpleFunction('githubCommit', [$this->url, 'githubCommitUrl']),
            new Twig_SimpleFunction('githubTreeish', [$this->url, 'githubTreeUrl']),
            new Twig_SimpleFunction('githubPullRequest', [$this->url, 'githubPullRequestUrl']),
            new Twig_SimpleFunction('githubReference', [$this->url, 'githubReferenceUrl']),
            new Twig_SimpleFunction('githubRelease', [$this->url, 'githubReleaseUrl']),

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

        try {
            $resolve = $this->github->resolve($user, $repo, $reference);
            $current = (is_array($resolve)) ? $resolve[1] : null;

            $isCurrent = ($current == $commit) ? true : false;

        // Fuck off weird errors
        } catch (Exception $ex) {
            $isCurrent = false;
        }

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
}
