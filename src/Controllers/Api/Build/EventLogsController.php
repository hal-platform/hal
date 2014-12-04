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
use QL\Hal\Core\Entity\Repository\BuildRepository;
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
     * @type BuildRepository
     */
    private $buildRepo;

    /**
     * @var EventLogNormalizer
     */
    private $eventLogNormalizer;

    /**
     * @var BuildNormalizer
     */
    private $buildNormalizer;

    /**
     * @param ResponseFormatter $formatter
     * @param BuildRepository $buildRepo
     * @param EventLogNormalizer $eventLogNormalizer
     * @param BuildNormalizer $buildNormalizer
     */
    public function __construct(
        ResponseFormatter $formatter,
        BuildRepository $buildRepo,
        EventLogNormalizer $eventLogNormalizer,
        BuildNormalizer $buildNormalizer
    ) {
        $this->formatter = $formatter;
        $this->buildRepo = $buildRepo;
        $this->eventLogNormalizer = $eventLogNormalizer;
        $this->buildNormalizer = $buildNormalizer;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @throws HttpProblemException
     */
    public function __invoke(Request $request, Response $response, array $params = [])
    {
        $build = $this->buildRepo->find($params['id']);

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
