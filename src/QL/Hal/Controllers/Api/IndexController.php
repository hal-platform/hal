<?php

namespace QL\Hal\Controllers\Api;

use Slim\Http\Request;
use Slim\Http\Response;
use QL\Hal\Helpers\ApiHelper;

/**
 * API Index Controller
 */
class IndexController
{
    /**
     * @var ApiHelper
     */
    private $api;

    /**
     * @param ApiHelper $api
     */
    public function __construct(
        ApiHelper $api
    ) {
        $this->api = $api;
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
            'self' => ['href' => 'api.index'],
            'logs' => ['href' => 'api.logs', 'type' => 'Logs'],
            'environments' => ['href' => 'api.environments', 'type' => 'Environments'],
            'servers' => ['href' => 'api.servers', 'type' => 'Servers'],
            'groups' => ['href' => 'api.groups', 'type' => 'Groups'],
            'users' => ['href' => 'api.users', 'type' => 'Users'],
            'repositories' => ['href' => 'api.repositories', 'type' => 'Repositories']
        ];

        $this->api->prepareResponse($response, $links, []);
    }
}
