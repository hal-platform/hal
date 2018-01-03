<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Twig;

use Exception;
use Hal\UI\VersionControl\VCS;
use QL\MCP\Cache\CachingTrait;
use Twig_Extension;
use Twig_SimpleFilter;
use Twig_SimpleFunction;

class GitHubExtension extends Twig_Extension
{
    use CachingTrait;

    const NAME = 'github';

    /**
     * @var VCS
     */
    private $vcs;

    /**
     * @param VCS $vcs
     */
    public function __construct(VCS $vcs)
    {
        $this->vcs = $vcs;
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @inheritDoc
     */
    public function getFunctions()
    {
        return [
            // new Twig_SimpleFunction('githubRepoUrl', [$this->urlBuilder, 'githubRepoURL']),
            // new Twig_SimpleFunction('githubCommitUrl', [$this->urlBuilder, 'githubCommitURL']),
            // new Twig_SimpleFunction('githubBranchUrl', [$this->urlBuilder, 'githubBranchURL']),
            // new Twig_SimpleFunction('githubPullRequestUrl', [$this->urlBuilder, 'githubPullRequestURL']),
            // new Twig_SimpleFunction('githubReferenceUrl', [$this->urlBuilder, 'githubReferenceURL']),
            // new Twig_SimpleFunction('githubReleaseUrl', [$this->urlBuilder, 'githubReleaseURL']),
            // new Twig_SimpleFunction('githubUserUrl', [$this->urlBuilder, 'githubUserUrl']),
            new Twig_SimpleFunction('githubRepoUrl', [$this, 'getFakeURL']),
            new Twig_SimpleFunction('githubCommitUrl', [$this, 'getFakeURL']),
            new Twig_SimpleFunction('githubBranchUrl', [$this, 'getFakeURL']),
            new Twig_SimpleFunction('githubPullRequestUrl', [$this, 'getFakeURL']),
            new Twig_SimpleFunction('githubReferenceUrl', [$this, 'getFakeURL']),
            new Twig_SimpleFunction('githubReleaseUrl', [$this, 'getFakeURL']),
            new Twig_SimpleFunction('githubUserUrl', [$this, 'getFakeURL']),

            new Twig_SimpleFunction('githubCommitIsCurrent', [$this, 'commitIsCurrent'])
        ];
    }

    /**
     * @inheritDoc
     */
    public function getFilters()
    {
        return [
            new Twig_SimpleFilter('gitref', [$this, 'resolveGitReference']),
            new Twig_SimpleFilter('commit', [$this, 'formatGitCommit'])
        ];
    }

    public function getFakeURL()
    {
        return 'https://github.example.com';
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
        // debug
        // debug
        // debug
        // debug
        return false;

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
     * @param string $reference
     *
     * @return string
     */
    public function formatGitCommit($reference)
    {
        return substr($reference, 0, 7);
    }

    /**
     * Format an arbitrary git reference for display
     *
     * @param string $reference
     *
     * @return array
     */
    public function resolveGitReference($reference)
    {
        // debug
        // debug
        // debug
        // debug
        return ['branch', $reference];

        if ($tag = $this->githubResolver->parseRefAsTag($reference)) {
            return ['tag', $tag];
        }

        if ($pull = $this->githubResolver->parseRefAsPull($reference)) {
            return ['pull', $pull];
        }

        if ($commit = $this->githubResolver->parseRefAsCommit($reference)) {
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
            $resolve = $this->githubResolver->resolve($user, $repo, $reference);
            $latest = (is_array($resolve)) ? $resolve[1] : null;

        // Fuck off weird errors
        } catch (Exception $ex) {
            $latest = null;
        }

        $this->setToCache($key, $latest, 15);

        return $latest;
    }
}
