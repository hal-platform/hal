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
use QL\Panthor\ControllerInterface;

class EventLogsController implements ControllerInterface
{
    use HypermediaResourceTrait;

    /**
     * @type ResponseFormatter
     */
    private $formatter;

    /**
     * @type PushRepository
     */
    private $pushRepo;

    /**
     * @type EventLogNormalizer
     */
    private $eventLogNormalizer;

    /**
     * @type PushNormalizer
     */
    private $pushNormalizer;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param ResponseFormatter $formatter
     * @param PushRepository $pushRepo
     * @param EventLogNormalizer $eventLogNormalizer
     * @param PushNormalizer $pushNormalizer
     * @param array $parameters
     */
    public function __construct(
        ResponseFormatter $formatter,
        PushRepository $pushRepo,
        EventLogNormalizer $eventLogNormalizer,
        PushNormalizer $pushNormalizer,
        array $parameters
    ) {
        $this->formatter = $formatter;
        $this->pushRepo = $pushRepo;
        $this->eventLogNormalizer = $eventLogNormalizer;
        $this->pushNormalizer = $pushNormalizer;

        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     * @throws HttpProblemException
     */
    public function __invoke()
    {
        $push = $this->pushRepo->find($this->parameters['id']);

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
