<?php

namespace QL\Hal\Controllers\Api\Push;

use QL\Hal\Core\Entity\Repository\PushRepository;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Hal\Core\Entity\Repository;
use Slim\Http\Request;
use Slim\Http\Response;
use QL\Hal\Helpers\ApiHelper;

/**
 * API Pushes Controller
 */
class PushesController
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
     * @var PushRepository
     */
    private $pushes;

    /**
     * @param ApiHelper $api
     * @param RepositoryRepository $repositories
     * @param PushRepository $pushes
     */
    public function __construct(
        ApiHelper $api,
        RepositoryRepository $repositories,
        PushRepository $pushes
    ) {
        $this->api = $api;
        $this->repositories = $repositories;
        $this->pushes = $pushes;
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
            'self' => ['href' => ['api.pushes', ['id' => $repository->getId()]], 'type' => 'Pushes'],
            'repository' => ['href' => ['api.repository', ['id' => $repository->getId()]], 'type' => 'Repository'],
            'index' => ['href' => 'api.index']
        ];

        $pushes = $this->pushes->getForRepository($repository);

        $content = [
            'count' => count($pushes),
            'pushes' => []
        ];

        foreach ($pushes as $push) {
            $content['pushes'][] = [
                'id' => $push->getId(),
                '_links' => $this->api->parseLinks([
                    'self' => ['href' => ['api.push', ['id' => $push->getId()]], 'type' => 'Push']
                ])
            ];
        }

        $this->api->prepareResponse($response, $links, $content);
    }
}
