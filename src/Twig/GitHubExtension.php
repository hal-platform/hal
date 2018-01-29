<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Twig;

use Exception;
use Hal\Core\Entity\Application;
use Hal\Core\Entity\JobType\Build;
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
            new TwigFunction('vcs_ref_url', [$this, 'formatVCSReferenceLink']),

            new TwigFunction('vcs_url', [$this, 'formatVCSLink']),
            new TwigFunction('vcs_text', [$this, 'formatVCSText']),

            new TwigFunction('is_vcs_ref_current', [$this, 'isVCSRefCurrent'])
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
     * Check if a vcs ref is the most recent for a given application build
     *
     * @param Application|null $application
     * @param Build|null $build
     *
     * @return bool
     */
    public function isVCSRefCurrent($application, $build)
    {
        if (!$application instanceof Application) {
            return false;
        }

        if (!$build instanceof Build) {
            return false;
        }

        $key = md5($application->id() . $build->reference());

        if (null !== ($latest = $this->getFromCache($key))) {
            return $latest;
        }

        $github = $this->vcs->authenticate($application->provider());
        if (!$github) {
            $this->setToCache($key, false, 15);
            return false;
        }

        $resolved = $github->resolver()->resolve(
            $application->parameter('gh.owner'),
            $application->parameter('gh.repo'),
            $build->reference()
        );

        if ($resolved) {
            $isCurrent = ($resolved[1] == $build->commit()) ? true : false;
        } else {
            $isCurrent = false;
        }

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
}
