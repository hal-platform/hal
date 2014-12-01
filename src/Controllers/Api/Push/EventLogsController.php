<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Push;

use QL\Hal\Api\EventLogNormalizer;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Entity\Repository\PushRepository;
use QL\Hal\Helpers\ApiHelper;
use Slim\Http\Request;
use Slim\Http\Response;

class EventLogsController
{
    /**
     * @type ApiHelper
     */
    private $api;

    /**
     * @type PushRepository
     */
    private $pushRepo;

    /**
     * @type EventLogNormalizer
     */
    private $normalizer;

    /**
     * @param ApiHelper $api
     * @param PushRepository $pushRepo
     * @param EventLogNormalizer $normalizer
     */
    public function __construct(ApiHelper $api, PushRepository $pushRepo, EventLogNormalizer $normalizer)
    {
        $this->api = $api;
        $this->pushRepo = $pushRepo;
        $this->normalizer = $normalizer;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     */
    public function __invoke(Request $request, Response $response, array $params = [])
    {
        $push = $this->pushRepo->find($params['id']);

        if (!$push instanceof Push) {
            return $response->setStatus(404);
        }

        $logs = $push->getLogs();

        $content = [
            'count' => count($logs),
            '_links' => [
                'self' => $this->api->parseLink(['href' => ['api.push.logs', ['id' => $push->getId()]]]),
                'push' => $this->api->parseLink(['href' => ['api.push', ['id' => $push->getId()]]])
            ]
        ];

        // If list is empty, return 404
        if (count($logs) === 0) {
            $this->api->prepareResponse($response, $content);
            return $response->setStatus(404);
        }

        $isResolved = false;

        $content = array_merge_recursive($content, $this->normalizeEventLogs($logs->toArray(), $isResolved));
        $this->api->prepareResponse($response, $content);
    }

    /**
     * @param array $logs
     * @param boolean $isResolved
     * @return array
     */
    private function normalizeEventLogs(array $logs, $isResolved)
    {
        $normalized = array_map(function($log) use ($isResolved) {
            if ($isResolved) {
                return $this->normalizer->normalize($log);
            }

            return $this->normalizer->linked($log);
        }, $logs);


        $type = ($isResolved) ? '_embedded' : '_links';
        return [
            $type => [
                'logs' => $normalized
            ]
        ];
    }
}
