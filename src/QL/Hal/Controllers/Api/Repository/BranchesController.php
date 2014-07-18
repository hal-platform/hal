<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Repository;

use Closure;
use QL\Hal\Core\Entity\Repository;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Hal\Helpers\ApiHelper;
use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Services\GithubService;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * API Repository Branches Controller
 */
class BranchesController
{
    /**
     * @type ApiHelper
     */
    private $api;

    /**
     * @type UrlHelper
     */
    private $url;

    /**
     * @type GithubService
     */
    private $github;

    /**
     * @type RepositoryRepository
     */
    private $repositoryRepo;

    /**
     * @param ApiHelper $api
     * @param UrlHelper $url
     * @param GithubService $github
     * @param RepositoryRepository $repositoryRepo
     */
    public function __construct(
        ApiHelper $api,
        UrlHelper $url,
        GithubService $github,
        RepositoryRepository $repositoryRepo
    ) {
        $this->api = $api;
        $this->url = $url;
        $this->github = $github;
        $this->repositoryRepo = $repositoryRepo;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     */
    public function __invoke(Request $request, Response $response, array $params = [])
    {
        $repository = $this->repositoryRepo->findOneBy(['id' => $params['id']]);
        if (!$repository instanceof Repository) {
            return $response->setStatus(404);
        }

        $branches = $this->github->branches($repository->getGithubUser(), $repository->getGithubRepo());
        if (!$branches) {
            return $response->setStatus(404);
        }

        usort($branches, $this->branchNameSort());

        $content = array_map(function($branch) use ($repository) {
            return [
                'name' => $branch['name'],
                'text' => $this->url->formatGitReference($branch['name']),
                'reference' => $branch['name'],
                'commit' => $branch['object']['sha'],
                'url' => $this->url->githubTreeUrl($repository->getGithubUser(), $repository->getGithubRepo(), $branch['name'])
            ];
        }, $branches);

        $this->api->prepareResponse($response, $content);
    }

    /**
     * Sorts master to top, rest alphabetically
     *
     * @return Closure
     */
    private function branchNameSort()
    {
        return function ($a, $b) {
            if ($a['name'] == 'master') {
                return -1;
            }

            if ($b['name'] == 'master') {
                return 1;
            }

            return strcmp($a['name'], $b['name']);
        };
    }
}
