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
            new Twig_SimpleFilter('gitref', [$this->url, 'formatGitReference']),
            new Twig_SimpleFilter('sliceGitref', [$this, 'sliceGitReference']),
            new Twig_SimpleFilter('commit', [$this->url, 'formatGitCommit'])
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
        $resolve = $this->github->resolve($user, $repo, $reference);
        $current = (is_array($resolve)) ? $resolve[1] : null;

        return ($current == $commit) ? true : false;
    }

    /**
     * @param string $gitref
     * @param int $size
     * @return string
     */
    public function sliceGitReference($gitref, $size = 30)
    {
        $value =  $this->url->formatGitReference($gitref);

        if (mb_strlen($value) <= $size + 3) {
            return $value;
        } else {
            return substr($value, 0, $size) . '...';
        }
    }
}
