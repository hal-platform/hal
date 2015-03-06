<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Twig;

use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Services\GithubService;
use Twig_Extension;
use Twig_SimpleFilter;
use Twig_SimpleFunction;
use Exception;

class GithubExtension extends Twig_Extension
{
    const NAME = 'github_permissions';

    /**
     * @type UrlHelper
     */
    private $url;

    /**
     * @type GithubService
     */
    private $github;

    /**
     * @param UrlHelper $url
     * @param GithubService $github
     */
    public function __construct(UrlHelper $url, GithubService $github)
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
     * @param $user
     * @param $repo
     * @param $reference
     * @param $commit
     *
     * @return bool
     */
    public function commitIsCurrent($user, $repo, $reference, $commit)
    {
        try {
            $resolve = $this->github->resolve($user, $repo, $reference);
            $current = (is_array($resolve)) ? $resolve[1] : null;

            return ($current == $commit) ? true : false;

        // Fuck off weird errors
        } catch (Exception $ex) {
            return false;
        }
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
