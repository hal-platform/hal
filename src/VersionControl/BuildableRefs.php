<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\VersionControl;

use Hal\Core\Entity\Application;
use Hal\Core\Parameters;
use Hal\Core\VersionControl\VCS;
use Hal\UI\Service\GitHubService;

class BuildableRefs
{
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
     * @param Application $application
     *
     * @return array
     */
    public function getVCSData(Application $application)
    {
        $service = $this->getVCSClient($application);
        if (!$service) {
            return [
                'gh_branches' => [],
                'gh_tags' => [],
                'gh_pr_open' => [],
                'gh_pr_closed' => []
            ];
        }

        ['service' => $github, 'params' => $params] = $service;

        return [
            'gh_branches' => $this->getGitHubBranches($github, $params),
            'gh_tags' => $this->getGitHubTags($github, $params),
            'gh_pr_open' => $this->getGitHubPullRequests($github, $params, true),
            'gh_pr_closed' => $this->getGitHubPullRequests($github, $params, false),
        ];
    }

    /**
     * @param Application $application
     *
     * @return array
     */
    private function getVCSClient(Application $application)
    {
        $provider = $application->provider();
        if (!$provider) {
            return [];
        }

        $github = $this->vcs->authenticate($provider);
        if (!$github) {
            return [];
        }

        $params = [
            $application->parameter(Parameters::VC_GH_OWNER),
            $application->parameter(Parameters::VC_GH_REPO)
        ];

        return [
            'service' => $github,
            'params' => $params
        ];
    }

    /**
     * Get an array of branches for an application
     *
     * @param GitHubService $github
     * @param array $params
     *
     * @return array
     */
    private function getGitHubBranches(GitHubService $github, array $params)
    {
        $branches = $github->branches(...$params);

        return $branches;
    }

    /**
     * Get an array of tags for an application
     *
     * @param GitHubService $github
     * @param array $params
     *
     * @return array
     */
    private function getGitHubTags(GitHubService $github, array $params)
    {
        $tags = $github->tags(...$params);

        return array_slice($tags, 0, 25);
        return $tags;
    }

    /**
     * Get pull requests, sort in descending order by number.
     *
     * @param GitHubService $github
     * @param array $params
     * @param bool $isOpenOnly
     *
     * @return array
     */
    private function getGitHubPullRequests(GitHubService $github, array $params, $isOpenOnly)
    {
        if ($isOpenOnly) {
            $pr = $github->openPullRequests(...$params);
        } else {
            $pr = $github->closedPullRequests(...$params);
        }

        return $pr;
    }
}
