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
use Hal\Core\Parameters;
use Hal\Core\Type\VCSProviderEnum;
use Hal\Core\Utility\CachingTrait;
use Hal\Core\VersionControl\VCSFactory;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class GitHubExtension extends AbstractExtension
{
    use CachingTrait;

    /**
     * @var VCSFactory
     */
    private $vcs;

    /**
     * @param VCSFactory $vcs
     */
    public function __construct(VCSFactory $vcs)
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

        if ($provider = $application->provider()) {
            if ($client = $this->vcs->authenticate($provider)) {
                return $client->urlForRepository(
                    $application->parameter(Parameters::VC_GH_OWNER),
                    $application->parameter(Parameters::VC_GH_REPO)
                );
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

        if ($provider = $application->provider()) {
            if ($client = $this->vcs->authenticate($provider)) {
                return $client->urlForReference(
                    $application->parameter(Parameters::VC_GH_OWNER),
                    $application->parameter(Parameters::VC_GH_REPO),
                    $reference
                );
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

        $githubs = [VCSProviderEnum::TYPE_GITHUB, VCSProviderEnum::TYPE_GITHUB_ENTERPRISE];

        if ($provider = $application->provider()) {
            if (in_array($provider->type(), $githubs)) {
                return sprintf(
                    '%s/%s',
                    $application->parameter(Parameters::VC_GH_OWNER),
                    $application->parameter(Parameters::VC_GH_REPO)
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

        if (!$provider = $application->provider()) {
            $this->setToCache($key, false, 15);
            return false;
        }

        if (!$client = $this->vcs->authenticate($provider)) {
            $this->setToCache($key, false, 15);
            return false;
        }

        $resolved = $client->resolveRef(
            $application->parameter(Parameters::VC_GH_OWNER),
            $application->parameter(Parameters::VC_GH_REPO),
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

        if ($provider = $application->provider()) {
            if ($client = $this->vcs->authenticate($provider)) {
                return $client->resolveRefType($reference);
            }
        }

        return ['branch', $reference];
    }
}
