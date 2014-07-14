<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api;

use DateTime;
use DateTimeZone;
use MCP\DataType\Time\Clock;
use Doctrine\ORM\EntityManager;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Entity\Repository\BuildRepository;
use QL\Hal\Core\Entity\Repository\PushRepository;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Get the current status of one or more jobs.
 *
 * @deprecated
 */
class QueueRefreshController
{
    /**
     * @var BuildRepository
     */
    private $buildRepo;

    /**
     * @var PushRepository
     */
    private $pushRepo;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var Clock
     */
    private $clock;

    /**
     * @var string
     */
    private $outputTimezone;

    /**
     * @param BuildRepository $buildRepo
     * @param PushRepository $pushRepo
     * @param EntityManager $entityManager
     * @param Clock $clock
     */
    public function __construct(
        BuildRepository $buildRepo,
        PushRepository $pushRepo,
        EntityManager $entityManager,
        Clock $clock
    ) {
        $this->buildRepo = $buildRepo;
        $this->pushRepo = $pushRepo;
        $this->entityManager = $entityManager;
        $this->clock = $clock;

        $this->outputTimezone = 'America/Detroit';
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @return null
     */
    public function __invoke(Request $request, Response $response, array $params = [])
    {
        if (!isset($params['uniqueId'])) {
            // Need some kind of error messaging
            return $response->setStatus(400);
        }

        $identifiers = explode(' ', $params['uniqueId']);

        if (!$jobs = $this->retrieveJobs($identifiers)) {
            return $response->setStatus(404);
        }

        $jobs = $this->formatQueue($jobs);

        $response->header('Content-Type', 'application/json; charset=utf-8');
        $response->body(json_encode($jobs));
    }

    /**
     * @param Build|Push $queue
     * @return array
     */
    private function formatQueue(array $queue)
    {
        $formattedQueue = [];

        foreach ($queue as $job) {
            if ($startTime = $job->getStart()) {
                $startTime = $startTime->format('M j, Y g:i A', $this->outputTimezone);
            }

            if ($endTime = $job->getEnd()) {
                $endTime = $endTime->format('M j, Y g:i A', $this->outputTimezone);
            }

            $formatted = [
                'id' => $job->getId(),
                'status' => $job->getStatus(),
                'startTime' => $startTime,
                'endTime' => $endTime
            ];

            if ($job instanceof Push) {
                $formatted = ['type' => 'Push', 'uniqueId' => 'push-' . $job->getId()] + $formatted;

            } else {
                $formatted = ['type' => 'Build', 'uniqueId' => 'build-' . $job->getId()] + $formatted;
            }

            $formattedQueue[] = $formatted;
        }

        return $formattedQueue;
    }

    /**
     * @param array $identifiers
     * @return array
     */
    private function retrieveJobs($identifiers)
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $builds = $pushes = [];

        if ($buildIds = $this->filterIdentifiers($identifiers, 'build-')) {
            $query = $queryBuilder
                ->select('b')
                ->from('QL\Hal\Core\Entity\Build', 'b')
                ->where($queryBuilder->expr()->in('b.id', '?1'))
                ->setParameters([1 => $buildIds]);
            $builds = $query->getQuery()->getResult();
        }

        if ($pushIds = $this->filterIdentifiers($identifiers, 'push-')) {
            $query = $queryBuilder
                ->select('p')
                ->from('QL\Hal\Core\Entity\Push', 'p')
                ->where($queryBuilder->expr()->in('p.id', '?1'))
                ->setParameters([1 => $pushIds]);
            $pushes = $query->getQuery()->getResult();
        }

        return array_merge($builds, $pushes);
    }

    /**
     * @param array $identifiers
     * @param string $prefix
     * @return array
     */
    private function filterIdentifiers(array $identifiers, $prefix)
    {
        $prefixLength = strlen($prefix);

        $filtered = array_filter($identifiers, function($v) use ($prefix, $prefixLength) {
            return (substr($v, 0, $prefixLength) === $prefix);
        });

        array_walk($filtered, function(&$v) use ($prefixLength) {
            $v = substr($v, $prefixLength);
        });

        return $filtered;
    }
}
