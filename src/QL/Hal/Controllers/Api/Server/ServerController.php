<?php

namespace QL\Hal\Controllers\Api\Server;

use QL\Hal\Core\Entity\Repository\ServerRepository;
use QL\Hal\Core\Entity\Server;
use QL\Hal\Helpers\UrlHelper;
use Slim\Http\Request;
use Slim\Http\Response;
use QL\Hal\Helpers\ApiHelper;

/**
 * API Server Controller
 */
class ServerController
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
     * @var ServerRepository
     */
    private $servers;

    /**
     * @param ApiHelper $api
     * @param UrlHelper $url
     * @param ServerRepository $servers
     */
    public function __construct(
        ApiHelper $api,
        UrlHelper $url,
        ServerRepository $servers
    ) {
        $this->api = $api;
        $this->url = $url;
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
        $server = $this->servers->findOneBy(['id' => $params['id']]);

        if (!($server instanceof Server)) {
            call_user_func($notFound);
            return;
        }

        $links = [
            'self' => ['href' => ['api.server', ['id' => $server->getId()]], 'type' => 'Server'],
            'index' => ['href' => 'api.index']
        ];

        $content = [
            'id' => $server->getId(),
            'url' => $this->url->urlFor('server', ['id' => $server->getId()]),
            'environment' => [
                'id' => $server->getEnvironment()->getId(),
                '_links' => $this->api->parseLinks([
                    'self' => ['href' => ['api.environment', ['id' => $server->getEnvironment()->getId()]], 'type' => 'Environment']
                ])
            ],
            'properties' => [],
            'deployments' => []
        ];

        foreach ($server->getProperties() as $property) {
            $content['properties'][] = [
                'id' => $property->getId(),
                'name' => $property->getName(),
                'value' => $property->getValue()
            ];
        }

        foreach ($server->getDeployments() as $deployment) {
            $content['deployments'][] = [
                'id' => $deployment->getId(),
                '_links' => $this->api->parseLinks([
                    'self' => ['href' => ['api.deployment', ['id' => $deployment->getId()]], 'type' => 'Deployment']
                ])
            ];
        }

        $this->api->prepareResponse($response, $links, $content);
    }
}
