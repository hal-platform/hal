<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api;

use DateTime;
use DateTimeZone;
use MCP\DataType\Time\TimePoint;
use Doctrine\ORM\EntityManager;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Entity\Repository\BuildRepository;
use QL\Hal\Core\Entity\Repository\PushRepository;
use Slim\Http\Request;
use Slim\Http\Response;

/**
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
     * @var string
     */
    private $timezone;

    /**
     * @param Twig_Template $template
     * @param Layout $layout
     * @param BuildRepository $buildRepo
     * @param PushRepository $pushRepo
     * @param EntityManager $entityManager
     */
    public function __construct(
        BuildRepository $buildRepo,
        PushRepository $pushRepo,
        EntityManager $entityManager
    ) {
        $this->buildRepo = $buildRepo;
        $this->pushRepo = $pushRepo;
        $this->entityManager = $entityManager;
        $this->timezone = 'America/Detroit';
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return null
     */
    public function __invoke(Request $request, Response $response)
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $query = $queryBuilder
            ->add('select', 'b')
            ->add('from', 'QL\Hal\Core\Entity\Build b')
            ->add('where', 'b.status = ?1 OR b.status = ?2')
            ->setParameters([1 => 'Waiting', 2 => 'Building']);
        $builds = $query->getQuery()->getResult();

        $query = $queryBuilder
            ->add('select', 'p')
            ->add('from', 'QL\Hal\Core\Entity\Push p')
            ->add('where', 'p.status = ?1 OR p.status = ?2')
            ->setParameters([1 => 'Waiting', 2 => 'Pushing']);
        $pushes = $query->getQuery()->getResult();

        $jobs = array_merge($builds, $pushes);

        // if a "since" parameter is specified, grab all finished (error AND success) jobs finished since that time.
        if ($finishedAfter = $this->parseValidSinceTime($request)) {
            $finishedStatus = [1 => 'Error', 2 => 'Success', 3 => $finishedAfter];

            $query = $queryBuilder
                ->add('select', 'b')
                ->add('from', 'QL\Hal\Core\Entity\Build b')
                ->add('where', '(b.status = ?1 OR b.status = ?2) AND b.end > ?3')
                ->setParameters($finishedStatus);
            $builds = $query->getQuery()->getResult();

            $query = $queryBuilder
                ->add('select', 'p')
               ->add('from', 'QL\Hal\Core\Entity\Push p')
               ->add('where', '(p.status = ?1 OR p.status = ?2) AND p.end > ?3')
               ->setParameters($finishedStatus);
            $pushes = $query->getQuery()->getResult();

            $jobs = array_merge($jobs, $builds, $pushes);
        }

        if (!$jobs) {
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
                $startTime = $startTime->format('M j, Y g:i A', $this->timezone);
            }

            if ($endTime = $job->getEnd()) {
                $endTime = $endTime->format('M j, Y g:i A', $this->timezone);
            }

            if ($job instanceof Push) {
                // push
                // retrieves: build, repository, environment, deployment, server
                $formattedQueue[] = [
                    'type' => 'Push',
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
     * Warning! We must format the time manually for the DQL builder. It is not smart enough to serialize the TimePoint
     * type even though it has been declared as a custom type in hal-core.
     *
     * Doing so is NOT DB platform agnostic. If the DB is switched from MySQL this format will need to change.
     *
     * TimePoint am cry :(
     *
     * @param Request $request
     * @return string|null
     */
    private function parseValidSinceTime(Request $request)
    {
        if (!$utcSince = $request->get('since')) {
            return null;
        }

        if (!$date = DateTime::createFromFormat(DateTime::W3C, $utcSince, new DateTimeZone('UTC'))) {
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
}
