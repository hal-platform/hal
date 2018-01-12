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
     * @param Application|null $application
     *
     * @return string
     */
    public function formatVCSLink($application): string
    {
        if (!$application instanceof Application) {
            return '';
        }

        $provider = $application->provider();
        $githubs = [VCSProviderEnum::TYPE_GITHUB, VCSProviderEnum::TYPE_GITHUB_ENTERPRISE];

        if ($provider) {
            if (in_array($provider->type(), $githubs)) {
                $github = $this->vcs->authenticate($application->provider());
                return $github->url()->githubRepoURL(
                    $application->parameter('gh.owner'),
                    $application->parameter('gh.repo')
                );

            } elseif ($provider === VCSProviderEnum::TYPE_GITHUB) {
                return $application->parameter('git.link');
            }
        }

        return '';
    }

    /**
     * @param Application|null $application
     * @param string $reference
     *
     * @return string
     */
    public function formatVCSReferenceLink($application, $reference): string
    {
        if (!$application instanceof Application) {
            return '';
        }

        $provider = $application->provider();
        $githubs = [VCSProviderEnum::TYPE_GITHUB, VCSProviderEnum::TYPE_GITHUB_ENTERPRISE];

        if ($provider) {
            if (in_array($provider->type(), $githubs)) {
                $github = $this->vcs->authenticate($application->provider());

                $ref = $github->resolver()->resolveRefType($reference);
                return $github->url()->githubRefURL(
                    $application->parameter('gh.owner'),
                    $application->parameter('gh.repo'),
                    ...$ref
                );

            } elseif ($provider === VCSProviderEnum::TYPE_GIT) {
                return '#';
            }
        }

        return '#';
    }

    /**
     * @param Application|null $application
     *
     * @return string
     */
    public function formatVCSText($application): string
    {
        if (!$application instanceof Application) {
            return '';
        }

        $provider = $application->provider();
        $githubs = [VCSProviderEnum::TYPE_GITHUB, VCSProviderEnum::TYPE_GITHUB_ENTERPRISE];

        if ($provider) {
            if (in_array($provider->type(), $githubs)) {
                return sprintf(
                    '%s/%s',
                    $application->parameter('gh.owner'),
                    $application->parameter('gh.repo')
                );

            } elseif ($provider->type() === VCSProviderEnum::TYPE_GIT) {
                return 'clone';
            }
        }

        return '';
    }

    /**
     * Check if a commit hash is the most recent for a given Github user, repo, and reference
     *
     * @param Application|null $application
     * @param string $reference
     * @param string $commit
     *
     * @return bool
     */
    public function commitIsCurrent($application, $reference, $commit)
    {
        // debug
        // debug
        // debug
        // debug
        return false;

        $user = '';
        $repo = '';

        // cache ref comparisons in memory
        $key = md5($user . $repo . $reference . $commit);

        if (null !== ($isCurrent = $this->getFromCache($key))) {
            return $isCurrent;
        }

        $latest = $this->resolveRefToLatestCommit($application, $reference);
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
    public function resolveVCSReference($reference, $application): array
    {
        if (!$application instanceof Application) {
            return ['branch', $reference];
        }

        $provider = $application->provider();
        $githubs = [VCSProviderEnum::TYPE_GITHUB, VCSProviderEnum::TYPE_GITHUB_ENTERPRISE];

        if ($provider) {
            if (in_array($provider->type(), $githubs)) {
                $github = $this->vcs->authenticate($provider);

                return $github->resolver()->resolveRefType($reference);
            }
        }

        return ['branch', $reference];
    }

    /**
     * Check if a commit hash is the most recent for a given Github user, repo, and reference
     *
     * @param Application $application
     * @param string $reference
     *
     * @return string|null
     */
    private function resolveRefToLatestCommit(Application $application, $reference)
    {
        $key = md5($application->id() . $reference);

        if (null !== ($latest = $this->getFromCache($key))) {
            return $latest;
        }

        // $resolve = $this->githubResolver->resolve($user, $repo, $reference);
        $resolve = '';
        $latest = (is_array($resolve)) ? $resolve[1] : null;


        $this->setToCache($key, $latest, 15);

        return $latest;
    }
}
