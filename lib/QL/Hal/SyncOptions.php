<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal;

use Github\Api\Repo as GithubRepoClient;
use QL\Hal\Services\DeploymentService;
use QL\Hal\Services\RepositoryService;

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
     * @var GithubRepoClient
     */
    private $githubRepoClient;

    public function __construct(
        RepositoryService $repoService,
        DeploymentService $depService,
        GithubRepoClient $githubRepoClient
    ) {
        $this->repoSerivce = $repoService;
        $this->depService = $depService;
        $this->githubRepoClient = $githubRepoClient;
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

        $branches = $this->githubRepoClient->branches($repo['GithubUser'], $repo['GithubRepo']);
        $tags = $this->githubRepoClient->tags($repo['GithubUser'], $repo['GithubRepo']);

        $data = [
            'deps' => $deps,
            'repo' => $repo,
            'branches' => $branches,
            'tags' => $tags,
        ];

        return $data;
    }
}
