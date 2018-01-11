<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Twig;

use Exception;
use Hal\Core\Entity\Application;
use Hal\Core\Type\VCSProviderEnum;
use Hal\UI\VersionControl\VCS;
use QL\MCP\Cache\CachingTrait;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\Extension\AbstractExtension;

class GitHubExtension extends AbstractExtension
{
    use CachingTrait;

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
    public function getFunctions()
    {
        return [
            // new TwigFunction('githubRepoUrl', [$this->urlBuilder, 'githubRepoURL']),
            // new TwigFunction('githubCommitUrl', [$this->urlBuilder, 'githubCommitURL']),
            // new TwigFunction('githubBranchUrl', [$this->urlBuilder, 'githubBranchURL']),
            // new TwigFunction('githubPullRequestUrl', [$this->urlBuilder, 'githubPullRequestURL']),
            // new TwigFunction('githubReferenceUrl', [$this->urlBuilder, 'githubReferenceURL']),
            // new TwigFunction('githubReleaseUrl', [$this->urlBuilder, 'githubReleaseURL']),
            // new TwigFunction('githubUserUrl', [$this->urlBuilder, 'githubUserUrl']),
            new TwigFunction('githubRepoUrl', [$this, 'getFakeURL']),
            new TwigFunction('githubCommitUrl', [$this, 'getFakeURL']),
            new TwigFunction('githubBranchUrl', [$this, 'getFakeURL']),
            new TwigFunction('githubPullRequestUrl', [$this, 'getFakeURL']),
            new TwigFunction('githubReferenceUrl', [$this, 'getFakeURL']),
            new TwigFunction('githubReleaseUrl', [$this, 'getFakeURL']),
            new TwigFunction('githubUserUrl', [$this, 'getFakeURL']),

            new TwigFunction('vcs_ref_url', [$this, 'formatVCSReferenceLink']),

            new TwigFunction('vcs_url', [$this, 'formatVCSLink']),
            new TwigFunction('vcs_text', [$this, 'formatVCSText']),

            new TwigFunction('githubCommitIsCurrent', [$this, 'commitIsCurrent'])
        ];
    }

    /**
     * @inheritDoc
     */
    public function getFilters()
    {
        return [
            new TwigFilter('vcsref', [$this, 'resolveVCSReference']),
            new TwigFilter('commit', [$this, 'formatVCSCommit'])
        ];
    }

    public function getFakeURL()
    {
        return 'https://github.example.com';
    }

    /**
     * Format a git commit hash for output
     *
     * @param string $reference
     *
     * @return string
     */
    public function formatVCSCommit($reference)
    {
        return substr($reference, 0, 7);
    }

    /**
     * @param Application|null $app
     *
     * @return string
     */
    public function formatVCSLink($app): string
    {
        if (!$app instanceof Application) {
            return '';
        }

        $githubs = [VCSProviderEnum::TYPE_GITHUB, VCSProviderEnum::TYPE_GITHUB_ENTERPRISE];

        if ($app->provider() && in_array($app->provider()->type(), $githubs)) {
            $github = $this->vcs->authenticate($app->provider());
            return $github->url()->githubRepoURL(
                $app->parameter('gh.owner'),
                $app->parameter('gh.repo')
            );

        } elseif ($app->provider() && $app->provider()->type() === VCSProviderEnum::TYPE_GITHUB) {
            return $app->parameter('git.link');
        }

        return '';
    }

    /**
     * @param Application|null $app
     * @param string $reference
     *
     * @return string
     */
    public function formatVCSReferenceLink($app, $reference): string
    {
        if (!$app instanceof Application) {
            return '';
        }

        $githubs = [VCSProviderEnum::TYPE_GITHUB, VCSProviderEnum::TYPE_GITHUB_ENTERPRISE];

        if ($app->provider() && in_array($app->provider()->type(), $githubs)) {
            $github = $this->vcs->authenticate($app->provider());

            $ref = $github->resolver()->resolveRefType($reference);
            return $github->url()->githubRefURL(
                $app->parameter('gh.owner'),
                $app->parameter('gh.repo'),
                ...$ref
            );

        } elseif ($app->provider() && $app->provider->type() === VCSProviderEnum::TYPE_GITHUB) {
            return '#';
        }

        return '#';
    }

    /**
     * @param Application|null
     *
     * @return string
     */
    public function formatVCSText($app): string
    {
        if (!$app instanceof Application) {
            return '';
        }

        $githubs = [VCSProviderEnum::TYPE_GITHUB, VCSProviderEnum::TYPE_GITHUB_ENTERPRISE];

        if ($app->provider() && in_array($app->provider()->type(), $githubs)) {
            return sprintf(
                '%s/%s',
                $app->parameter('gh.owner'),
                $app->parameter('gh.repo')
            );

        } elseif ($app->provider() && $app->provider->type() === VCSProviderEnum::TYPE_GITHUB) {
            return 'clone';
        }

        return '';
    }

    /**
     * Check if a commit hash is the most recent for a given Github user, repo, and reference
     *
     * @param Application|null $application
     * @param string $repo
     * @param string $reference
     * @param string $commit
     *
     * @return bool
     */
    public function commitIsCurrent($app,  $reference, $commit)
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
     * Format an arbitrary git reference for display
     *
     * @param string $reference
     * @param Application|mixed $application
     *
     * @return array
     */
    public function resolveVCSReference($reference, $app): array
    {
        if (!$app instanceof Application) {
            return ['branch', $reference];
        }

        $githubs = [VCSProviderEnum::TYPE_GITHUB, VCSProviderEnum::TYPE_GITHUB_ENTERPRISE];

        if ($app->provider() && in_array($app->provider()->type(), $githubs)) {
            $github = $this->vcs->authenticate($app->provider());

            return $github->resolver()->resolveRefType($reference);
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
