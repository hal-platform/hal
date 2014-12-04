<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api;

use QL\Hal\Api\Normalizer\EventLogNormalizer;
use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Core\Entity\EventLog;
use QL\Hal\Core\Entity\Repository\EventLogRepository;
use QL\HttpProblem\HttpProblemException;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 *
 */
class EventLogController
{
    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @type EventLogRepository
     */
    private $repository;

    /**
     * @var EventLogNormalizer
     */
    private $normalizer;

    /**
     * @param ResponseFormatter $formatter
     * @param EventLogRepository $repository
     * @param EventLogNormalizer $normalizer
     */
    public function __construct(
        ResponseFormatter $formatter,
        EventLogRepository $repository,
        EventLogNormalizer $normalizer
    ) {
        $this->formatter = $formatter;
        $this->repository = $repository;
        $this->normalizer = $normalizer;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @throws HttpProblemException
     */
    public function __invoke(Request $request, Response $response, array $params = [])
    {
        $log = $this->repository->find($params['id']);

        if (!$log instanceof EventLog) {
            throw HttpProblemException::build(404, 'invalid-log');
        }

        $this->formatter->respond($log);
    }
}
