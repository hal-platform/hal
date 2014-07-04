<?php

namespace QL\Hal\Controllers\Api;

use QL\Hal\Core\Entity\Repository\BuildRepository;
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
     * @var BuildRepository
     */
    private $builds;

    /**
     * @param ApiHelper $api
     * @param BuildRepository $builds
     */
    public function __construct(
        ApiHelper $api,
        BuildRepository $builds
    ) {
        $this->api = $api;
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
        $links = [
            'self' => ['href' => ['api.builds', ['id' => $params['id']]], 'type' => 'Builds'],
            'repository' => ['href' => ['api.repository', ['id' => $params['id']]], 'type' => 'Repository'],
            'index' => ['href' => 'api.index']
        ];

        $builds = $this->builds->findBy(['repository' => $params['id']], ['status' => 'ASC', 'start' => 'DESC']);

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
