<?php

namespace QL\Hal\Controllers\Api\Server;

use QL\Hal\Core\Entity\Repository\ServerRepository;
use Slim\Http\Request;
use Slim\Http\Response;
use QL\Hal\Helpers\ApiHelper;

/**
 * API Server Controller
 */
class ServersController
{
    /**
     * @var ApiHelper
     */
    private $api;

    private $servers;

    /**
     * @param ApiHelper $api
     * @param ServerRepository $servers
     */
    public function __construct(
        ApiHelper $api,
        ServerRepository $servers
    ) {
        $this->api = $api;
        $this->servers = $servers;
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
            'self' => ['href' => 'api.servers', 'type' => 'Servers'],
            'index' => ['href' => 'api.index']
        ];

        $servers = $this->servers->findBy([], ['id' => 'DESC']);

        $content = [
            'count' => count($servers),
            'servers' => []
        ];

        foreach ($servers as $server) {
            $content['servers'][] = [
                'id' => $server->getId(),
                '_links' => $this->api->parseLinks([
                    'self' => ['href' => ['api.server', ['id' => $server->getId()]]]
                ])
            ];
        }

        $this->api->prepareResponse($response, $links, $content);
    }
}
