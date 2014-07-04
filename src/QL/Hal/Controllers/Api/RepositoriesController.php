<?php

namespace QL\Hal\Controllers\Api;

use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use Slim\Http\Request;
use Slim\Http\Response;
use QL\Hal\Helpers\ApiHelper;

/**
 * API Repositories Controller
 */
class RepositoriesController
{
    /**
     * @var ApiHelper
     */
    private $api;

    /**
     * @var RepositoryRepository
     */
    private $repositories;

    /**
     * @param ApiHelper $api
     * @param RepositoryRepository $repositories
     */
    public function __construct(
        ApiHelper $api,
        RepositoryRepository $repositories
    ) {
        $this->api = $api;
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
        $links = [
            'self' => ['href' => 'api.repositories'],
            'index' => ['href' => 'api.index']
        ];

        $repositories = $this->repositories->findBy([], ['id' => 'ASC']);

        $content = [
            'count' => count($repositories),
            'repositories' => []
        ];

        foreach ($repositories as $repository) {
            $content['repositories'][] = [
                'id' => $repository->getId(),
                '_links' => $this->api->parseLinks([
                    'self' => ['href' => ['api.repository', ['id' => $repository->getId()]]]
                ])
            ];
        }

        $this->api->prepareResponse($response, $links, $content);
    }
}
