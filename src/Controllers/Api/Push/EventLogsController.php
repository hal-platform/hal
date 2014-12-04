<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Push;

use QL\Hal\Api\Normalizer\EventLogNormalizer;
use QL\Hal\Api\Normalizer\PushNormalizer;
use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Entity\Repository\PushRepository;
use QL\HttpProblem\HttpProblemException;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 *
 */
class EventLogsController
{
    use HypermediaResourceTrait;

    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @type PushRepository
     */
    private $pushRepo;

    /**
     * @var EventLogNormalizer
     */
    private $eventLogNormalizer;

    /**
     * @var PushNormalizer
     */
    private $pushNormalizer;

    /**
     * @param ResponseFormatter $formatter
     * @param PushRepository $pushRepo
     * @param EventLogNormalizer $eventLogNormalizer
     * @param PushNormalizer $pushNormalizer
     */
    public function __construct(
        ResponseFormatter $formatter,
        PushRepository $pushRepo,
        EventLogNormalizer $eventLogNormalizer,
        PushNormalizer $pushNormalizer
    ) {
        $this->formatter = $formatter;
        $this->pushRepo = $pushRepo;
        $this->eventLogNormalizer = $eventLogNormalizer;
        $this->pushNormalizer = $pushNormalizer;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @throws HttpProblemException
     */
    public function __invoke(Request $request, Response $response, array $params = [])
    {
        $push = $this->pushRepo->find($params['id']);

        if (!$push instanceof Push) {
            throw HttpProblemException::build(404, 'invalid-push');
        }

        $logs = array_map(function ($log) {
            return $this->eventLogNormalizer->link($log);
        }, $push->getLogs()->toArray());

        $status = (count($logs) > 0) ? 200 : 404;

        $this->formatter->respond($this->buildResource(
            [
                'count' => count($logs)
            ],
            [],
            [
                'push' => $this->pushNormalizer->link($push),
                'logs' => $logs
            ]
        ), $status);
    }
}
