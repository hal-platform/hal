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
 * Get all pushes and builds created after the specified time.
 *
 * If no time is provided (Get param = "since"), all jobs in the past 20 minutes will be retrieved.
 *
 * This will be replaced. I just kind of want it here as a prototype.
 *
 * @deprecated
 */
class QueueController
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
     * @return null
     */
    public function __invoke(Request $request, Response $response)
    {
        $since = $request->get('since');
        $createdAfter = null;
        if ($since && !$createdAfter = $this->parseValidSinceTime($since)) {
            // Need some kind of error messaging
            return $response->setStatus(400);
        }

        $createdAfter = $createdAfter ?: $this->getDefaultSinceTime();

        if (!$jobs = $this->retrieveJobs($createdAfter)) {
            return $response->setStatus(404);
        }

        usort($jobs, $this->queueSort());

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

            if ($job instanceof Push) {
                // push
                // retrieves: build, repository, environment, deployment, server
                $formattedQueue[] = [
                    'type' => 'Push',
                    'uniqueId' => 'push-' . $job->getId(),
                    'id' => $job->getId(),
                    'status' => $job->getStatus(),
                    'startTime' => $startTime,
                    'endTime' => $endTime,
                    'repository' => [
                        'id' => $job->getBuild()->getRepository()->getId(),
                        'name' => $job->getBuild()->getRepository()->getKey()
                    ],
                    'environment' => [
                        'name' => $job->getBuild()->getEnvironment()->getKey()
                    ],
                    'server' => [
                        'name' => $job->getDeployment()->getServer()->getName()
                    ]
                ];
            } else {
                // build
                // retrieves: repository, environment
                $formattedQueue[] = [
                    'type' => 'Build',
                    'uniqueId' => 'build-' . $job->getId(),
                    'id' => $job->getId(),
                    'status' => $job->getStatus(),
                    'startTime' => $startTime,
                    'endTime' => $endTime,
                    'repository' => [
                        'id' => $job->getRepository()->getId(),
                        'name' => $job->getRepository()->getKey()
                    ],
                    'environment' => [
                        'name' => $job->getEnvironment()->getKey()
                    ]
                ];
            }
        }

        return $formattedQueue;
    }

    /**
     * If no filter is specified, only get builds created in the last 20 minutes.
     *
     * @return string
     */
    private function getDefaultSinceTime()
    {
        $time = $this->clock->read();
        $time = $time->modify('-20 minutes');
        return $time->format('Y-m-d H:i:s', 'UTC');
    }

    /**
     * Warning! We must format the time manually for the DQL builder. It is not smart enough to serialize the TimePoint
     * type even though it has been declared as a custom type in hal-core.
     *
     * Doing so is NOT DB platform agnostic. If the DB is switched from MySQL this format will need to change.
     *
     * TimePoint am cry :(
     *
     * @param string $since
     * @return string|null
     */
    private function parseValidSinceTime($since)
    {
        if (!$date = DateTime::createFromFormat(DateTime::W3C, $since, new DateTimeZone('UTC'))) {
            return null;
        }

        return $date->format('Y-m-d H:i:s');
    }

    /**
     * @return Closure
     */
    private function queueSort()
    {
        return function($aEntity, $bEntity) {
            $a = $aEntity->getStart();
            $b = $bEntity->getStart();

            if ($a === $b) {
                return 0;
            }

            if ($a === null xor $b === null) {
                return ($a === null) ? 0 : 1;
            }

            if ($a < $b) {
                return 1;
            }

            return -1;
        };
    }

    /**
     * @param string $since
     * @return array
     */
    private function retrieveJobs($since)
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $query = $queryBuilder
            ->select('b')
            ->from('QL\Hal\Core\Entity\Build', 'b')
            ->where('b.created >= ?1')
            ->setParameters([1 => $since]);
        $builds = $query->getQuery()->getResult();

        $query = $queryBuilder
            ->select('p')
            ->from('QL\Hal\Core\Entity\Push', 'p')
            ->where('p.created >= ?1')
            ->setParameters([1 => $since]);
        $pushes = $query->getQuery()->getResult();

        return array_merge($builds, $pushes);
    }
}
