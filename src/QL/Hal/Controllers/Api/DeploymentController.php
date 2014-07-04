<?php

namespace QL\Hal\Controllers\Api;

use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Repository\DeploymentRepository;
use QL\Hal\Helpers\UrlHelper;
use Slim\Http\Request;
use Slim\Http\Response;
use QL\Hal\Helpers\ApiHelper;

/**
 * API Deployment Controller
 */
class DeploymentController
{
    /**
     * @var ApiHelper
     */
    private $api;

    /**
     * @var DeploymentRepository
     */
    private $deployments;

    /**
     * @param ApiHelper $api
     * @param DeploymentRepository $deployments
     */
    public function __construct(
        ApiHelper $api,
        DeploymentRepository $deployments
    ) {
        $this->api = $api;
        $this->deployments = $deployments;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        $deployment = $this->deployments->findOneBy(['id' => $params['id']]);

        if (!($deployment instanceof Deployment)) {
            call_user_func($notFound);
            return;
        }

        $links = [
            'self' => ['href' => ['api.deployment', ['id' => $deployment->getId()]], 'type' => 'Deployment'],
            'index' => ['href' => 'api.index']
        ];

        $content = [
            'id' => $deployment->getId(),
            'path' => $deployment->getPath(),
            'repository' => [
                'id' => $deployment->getRepository()->getId(),
                '_links' => $this->api->parseLinks([
                    'self' => ['href' => ['api.repository', ['id' => $deployment->getRepository()->getId()]], 'type' => 'Repository']
                ])
            ],
            'server' => [
                'id' => $deployment->getServer()->getId(),
                '_links' => $this->api->parseLinks([
                    'self' => ['href' => ['api.server', ['id' => $deployment->getServer()->getId()]], 'type' => 'Server']
                ])
            ],
            'status' => [
                '_links' => $this->api->parseLinks([
                    'self' => ['href' => ['api.deployment.status', ['id' => $deployment->getId()]], 'type' => 'Deployment Status']
                ])
            ]
        ];

        $this->api->prepareResponse($response, $links, $content);
    }
}
