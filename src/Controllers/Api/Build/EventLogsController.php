<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Build;

use QL\Hal\Api\Normalizer\BuildNormalizer;
use QL\Hal\Api\Normalizer\EventLogNormalizer;
use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Repository\BuildRepository;
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
     * @type BuildRepository
     */
    private $buildRepo;

    /**
     * @type EventLogNormalizer
     */
    private $eventLogNormalizer;

    /**
     * @type BuildNormalizer
     */
    private $buildNormalizer;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param ResponseFormatter $formatter
     * @param BuildRepository $buildRepo
     * @param EventLogNormalizer $eventLogNormalizer
     * @param BuildNormalizer $buildNormalizer
     * @param array $parameters
     */
    public function __construct(
        ResponseFormatter $formatter,
        BuildRepository $buildRepo,
        EventLogNormalizer $eventLogNormalizer,
        BuildNormalizer $buildNormalizer,
        array $parameters
    ) {
        $this->formatter = $formatter;
        $this->buildRepo = $buildRepo;
        $this->eventLogNormalizer = $eventLogNormalizer;
        $this->buildNormalizer = $buildNormalizer;

        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     * @throws HttpProblemException
     */
    public function __invoke()
    {
        $build = $this->buildRepo->find($this->parameters['id']);

        if (!$build instanceof Build) {
            throw HttpProblemException::build(404, 'invalid-build');
        }

        $logs = array_map(function ($log) {
            return $this->eventLogNormalizer->link($log);
        }, $build->getLogs()->toArray());

        $status = (count($logs) > 0) ? 200 : 404;

        $this->formatter->respond($this->buildResource(
            [
                'count' => count($logs)
            ],
            [],
            [
                'build' => $this->buildNormalizer->link($build),
                'logs' => $logs
            ]
        ), $status);
    }
}
