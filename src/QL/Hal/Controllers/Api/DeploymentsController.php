<?php

namespace QL\Hal\Controllers\Api;

use QL\Hal\Core\Entity\Repository\DeploymentRepository;
use Slim\Http\Request;
use Slim\Http\Response;
use QL\Hal\Helpers\ApiHelper;

/**
 * API Deployments Controller
 */
class DeploymentsController
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
        $links = [
            'self' => ['href' => ['api.deployments', ['id' => $params['id']]], 'type' => 'Deployments'],
            'index' => ['href' => 'api.index']
        ];

        $deployments = $this->deployments->findBy(['repository' => $params['id']], ['id' => 'ASC']);

        $content = [
            'count' => count($deployments),
            'deployments' => []
        ];

        foreach ($deployments as $deployment) {
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
