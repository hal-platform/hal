<?php

namespace QL\Hal\Controllers\Api;

use QL\Hal\Core\Entity\Repository\EnvironmentRepository;
use Slim\Http\Request;
use Slim\Http\Response;
use QL\Hal\Helpers\ApiHelper;

/**
 * API Environments Controller
 */
class EnvironmentsController
{
    /**
     * @var ApiHelper
     */
    private $api;

    /**
     * @var EnvironmentRepository
     */
    private $environments;

    /**
     * @param ApiHelper $api
     * @param EnvironmentRepository $environments
     */
    public function __construct(
        ApiHelper $api,
        EnvironmentRepository $environments
    ) {
        $this->api = $api;
        $this->environments = $environments;
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
            'self' => ['href' => 'api.environments'],
            'index' => ['href' => 'api.index']
        ];

        $environments = $this->environments->findBy([], ['id' => 'ASC']);

        $content = [
            'count' => count($environments),
            'environments' => []
        ];

        foreach ($environments as $environment) {
            $content['environments'][] = [
                'id' => $environment->getId(),
                '_links' => $this->api->parseLinks([
                    'self' => ['href' => ['api.environment', ['id' => $environment->getId()]], 'type' => 'Environment']
                ])
            ];
        }

        $this->api->prepareResponse($response, $links, $content);
    }
}
