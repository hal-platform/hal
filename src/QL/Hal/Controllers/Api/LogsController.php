<?php

namespace QL\Hal\Controllers\Api;

use QL\Hal\Core\Entity\Repository\LogRepository;
use QL\Hal\Helpers\TimeHelper;
use Slim\Http\Request;
use Slim\Http\Response;
use QL\Hal\Helpers\ApiHelper;

/**
 * API Logs Controller
 */
class LogsController
{
    /**
     * @var ApiHelper
     */
    private $api;

    /**
     * @var TimeHelper
     */
    private $time;

    /**
     * @var LogRepository
     */
    private $logs;

    /**
     * @param ApiHelper $api
     * @param TimeHelper $time
     * @param LogRepository $logs
     */
    public function __construct(
        ApiHelper $api,
        TimeHelper $time,
        LogRepository $logs
    ) {
        $this->api = $api;
        $this->time = $time;
        $this->logs = $logs;
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
            'self' => ['href' => 'api.logs', 'type' => 'Logs'],
            'index' => ['href' => 'api.index']
        ];

        $logs = $this->logs->findBy([], ['recorded' => 'DESC']);

        $content = [
            'count' => count($logs),
            'logs' => []
        ];

        foreach ($logs as $log) {
            $content['logs'][] = [
                'id' => $log->getId(),
                'date' => [
                    'text' => $this->time->relative($log->getRecorded(), false),
                    'datetime' => $this->time->format($log->getRecorded(), false, 'c')
                ],
                'entity' => $log->getEntity(),
                'action' => $log->getAction(),
                'changeset' => $log->getData(),
                'user' => [
                    'id' => $log->getUser()->getId(),
                    '_links' => $this->api->parseLinks([
                        'self' => ['href' => ['api.user', ['id' => $log->getUser()->getId()]], 'type' => 'User']
                    ])
                ]
            ];
        }

        $this->api->prepareResponse($response, $links, $content);
    }
}
