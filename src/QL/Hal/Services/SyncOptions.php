<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Services;

use QL\Hal\Services\GithubService;

/**
 * @api
 */
class SyncOptions
{
    /**
     * @var RepositoryService
     */
    private $repoSerivce;

    /**
     * @var DeploymentService
     */
    private $depService;

    /**
     * @var GithubService
     */
    private $github;

    /**
     * @param RepositoryService $repoService
     * @param DeploymentService $depService
     * @param GithubService $github
     */
    public function __construct(RepositoryService $repoService, DeploymentService $depService, GithubService $github)
    {
        $this->repoSerivce = $repoService;
        $this->depService = $depService;
        $this->github = $github;
    }

    /**
     * @param string $repoShortName
     * @param int[] $deploymentIds
     * @return array
     */
    public function syncOptionsByRepoShortName(
        $repoShortName,
        array $deploymentIds
    ) {
        $repo = $this->repoSerivce->getFromName($repoShortName);
        if (!$repo) {
            return [];
        }

        $deps = [];
        foreach ($deploymentIds as $depId) {
            $dep = $this->depService->getById($depId);
            if ($dep && $dep['RepositoryId'] == $repo['RepositoryId']) {
                $deps[] = $dep;
            }
        }

        if (!$deps) {
            return ['repo' => $repo, 'deps' => []];
        }

        $branches = $this->github->branches($repo['GithubUser'], $repo['GithubRepo']);
        $tags = $this->github->tags($repo['GithubUser'], $repo['GithubRepo']);

        $data = [
            'deps' => $deps,
            'repo' => $repo,
            'branches' => $branches,
            'tags' => $tags,
        ];

        return $data;
    }
}
