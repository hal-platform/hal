<?php

namespace QL\Hal\Controllers\Api;

use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Hal\Core\Entity\Repository;
use QL\Hal\Helpers\UrlHelper;
use Slim\Http\Request;
use Slim\Http\Response;
use QL\Hal\Helpers\ApiHelper;

/**
 * API Repository Controller
 */
class RepositoryController
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
     * @var RepositoryRepository
     */
    private $repositories;

    /**
     * @param ApiHelper $api
     * @param UrlHelper $url
     * @param RepositoryRepository $repositories
     */
    public function __construct(
        ApiHelper $api,
        UrlHelper $url,
        RepositoryRepository $repositories
    ) {
        $this->api = $api;
        $this->url = $url;
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
            'self' => ['href' => ['api.repository', ['id' => $repository->getId()]], 'type' => 'Repository'],
            'repositories' => ['href' => 'api.repositories', 'type' => 'Repositories'],
            'index' => ['href' => 'api.index']
        ];

        $content = [
            'id' => $repository->getId(),
            'url' => $this->url->urlFor('repository', ['id' => $repository->getId()]),
            'key' => $repository->getKey(),
            'description' => $repository->getDescription(),
            'email' => $repository->getEmail(),
            'githubUser' => [
                'text' => $repository->getGithubUser(),
                'url' => $this->url->githubUserUrl($repository->getGithubUser())
            ],
            'githubRepo' => [
                'text' => $repository->getGithubRepo(),
                'url' => $this->url->githubRepoUrl($repository->getGithubUser(), $repository->getGithubRepo())
            ],
            'buildCmd' => $repository->getBuildCmd(),
            'prePushCmd' => $repository->getPrePushCmd(),
            'postPushCmd' => $repository->getPostPushCmd(),
            'group' => [
                'id' => $repository->getGroup()->getId(),
                '_links' => $this->api->parseLinks([
                    'self' => ['href' => ['api.group', ['id' => $repository->getGroup()->getId()]], 'type' => 'Group']
                ])
            ],
            '_links' => $this->api->parseLinks([
                'deployments' => ['href' => ['api.deployments', ['id' => $repository->getId()]], 'type' => 'Deployments'],
                'builds' => ['href' => ['api.builds', ['id' => $repository->getId()]], 'type' => 'Builds'],
                'pushes' => ['href' => ['api.pushes', ['id' => $repository->getId()]], 'type' => 'Pushes']
            ]),
        ];

        $this->api->prepareResponse($response, $links, $content);
    }
}
