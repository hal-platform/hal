<?php

namespace QL\Hal\Controllers\Api\Repository;

use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Hal\Helpers\UrlHelper;
use Slim\Http\Request;
use Slim\Http\Response;
use QL\Hal\Helpers\ApiHelper;
use QL\Hal\Services\GithubService;
use QL\Hal\Core\Entity\Repository;

/**
 * API Repository Branches Controller
 */
class BranchesController
{
    /**
     * @var ApiHelper
     */
    private $api;

    /**
     * @var UrlHelper
     */
    private $url;

    /**
     * @var GithubService
     */
    private $github;

    /**
     * @var RepositoryRepository
     */
    private $repositories;

    /**
     * @param ApiHelper $api
     * @param UrlHelper $url
     * @param GithubService $github
     * @param RepositoryRepository $repositories
     */
    public function __construct(
        ApiHelper $api,
        UrlHelper $url,
        GithubService $github,
        RepositoryRepository $repositories
    ) {
        $this->api = $api;
        $this->url = $url;
        $this->github = $github;
        $this->repositories = $repositories;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        $repository = $this->repositories->findOneBy(['id' => $params['id']]);

        if (!($repository instanceof Repository)) {
            call_user_func($notFound);
            return;
        }

        $links = [
            'self' => ['href' => ['api.repository.branches', ['id' => $repository->getId()]], 'type' => 'Repository Tags'],
            'repository' => ['href' => ['api.repository', ['id' => $repository->getId()]], 'type' => 'Repository'],
            'index' => ['href' => 'api.index']
        ];

        $branches = $this->github->branches(
            $repository->getGithubUser(),
            $repository->getGithubRepo()
        );

        // sort master to top, rest alphabetically
        usort($branches, function ($a, $b) {
            if ($a['name'] == 'master') {
                return -1;
            }
            if ($b['name'] == 'master') {
                return 1;
            }
            return ($a['name'] > $b['name']);
        });

        $content = [
            'count' => count($branches),
            'branches' => []
        ];

        foreach ($branches as $branch) {
            $content['branches'][] = [
                'name' => $branch['name'],
                'text' => $this->url->formatGitReference($branch['name']),
                'reference' => $branch['name'],
                'commit' => $branch['object']['sha'],
                'url' => $this->url->githubTreeUrl($repository->getGithubUser(),$repository->getGithubRepo(),$branch['name'])
            ];
        }

        $this->api->prepareResponse($response, $links, $content);
    }
}
