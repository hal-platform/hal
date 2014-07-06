<?php

namespace QL\Hal\Controllers\Api\Build;

use QL\Hal\Core\Entity\Repository\BuildRepository;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Hal\Core\Entity\Repository;
use Slim\Http\Request;
use Slim\Http\Response;
use QL\Hal\Helpers\ApiHelper;

/**
 * API Builds Controller
 */
class BuildsController
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
     * @var BuildRepository
     */
    private $builds;

    /**
     * @param ApiHelper $api
     * @param RepositoryRepository $repositories
     * @param BuildRepository $builds
     */
    public function __construct(
        ApiHelper $api,
        RepositoryRepository $repositories,
        BuildRepository $builds
    ) {
        $this->api = $api;
        $this->repositories = $repositories;
        $this->builds = $builds;
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
            'self' => ['href' => ['api.builds', ['id' => $repository->getId()]], 'type' => 'Builds'],
            'repository' => ['href' => ['api.repository', ['id' => $repository->getId()]], 'type' => 'Repository'],
            'index' => ['href' => 'api.index']
        ];

        $builds = $this->builds->findBy(['repository' => $repository->getId()], ['status' => 'ASC', 'start' => 'DESC']);

        $content = [
            'count' => count($builds),
            'builds' => []
        ];

        foreach ($builds as $build) {
            $content['builds'][] = [
                'id' => $build->getId(),
                '_links' => $this->api->parseLinks([
                    'self' => ['href' => ['api.build', ['id' => $build->getId()]]]
                ])
            ];
        }

        $this->api->prepareResponse($response, $links, $content);
    }
}
