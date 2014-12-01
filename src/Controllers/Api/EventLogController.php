<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api;

use QL\Hal\Api\EventLogNormalizer;
use QL\Hal\Core\Entity\EventLog;
use QL\Hal\Core\Entity\Repository\EventLogRepository;
use QL\Hal\Helpers\ApiHelper;
use Slim\Http\Request;
use Slim\Http\Response;

class EventLogController
{
    /**
     * @type ApiHelper
     */
    private $api;

    /**
     * @type EventLogRepository
     */
    private $repository;

    /**
     * @type EventLogNormalizer
     */
    private $normalizer;

    /**
     * @param ApiHelper $api
     * @param EventLogRepository $repository
     * @param EventLogNormalizer $normalizer
     */
    public function __construct(ApiHelper $api, EventLogRepository $repository, EventLogNormalizer $normalizer)
    {
        $this->api = $api;
        $this->repository = $repository;
        $this->normalizer = $normalizer;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     */
    public function __invoke(Request $request, Response $response, array $params = [])
    {
        $log = $this->repository->find($params['id']);

        if (!$log instanceof EventLog) {
            return $response->setStatus(404);
        }

        $this->api->prepareResponse($response, $this->normalizer->normalize($log));
    }
}
