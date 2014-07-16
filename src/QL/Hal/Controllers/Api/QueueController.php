<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api;

use DateTime;
use DateTimeZone;
use Doctrine\Common\Collections\Criteria;
use MCP\DataType\Time\Clock;
use MCP\DataType\Time\TimePoint;
use QL\Hal\Api\BuildNormalizer;
use QL\Hal\Api\PushNormalizer;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Entity\Repository\BuildRepository;
use QL\Hal\Core\Entity\Repository\PushRepository;
use QL\Hal\Helpers\ApiHelper;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Get all pushes and builds created after the specified time.
 *
 * If no time is provided (Get param = "since"), all jobs in the past 20 minutes will be retrieved.
 * @todo change "since" to use some kind of better query filtering.
 */
class QueueController
{
    /**
     * @var ApiHelper
     */
    private $api;

    /**
     * @var BuildNormalizer
     */
    private $buildNormalizer;

    /**
     * @var PushNormalizer
     */
    private $pushNormalizer;

    /**
     * @var BuildRepository
     */
    private $buildRepo;

    /**
     * @var PushRepository
     */
    private $pushRepo;

    /**
     * @var Clock
     */
    private $clock;

    /**
     * @param ApiHelper $api
     * @param BuildNormalizer $buildNormalizer
     * @param PushNormalizer $pushNormalizer
     * @param BuildRepository $buildRepo
     * @param PushRepository $pushRepo
     * @param EntityManager $entityManager
     * @param Clock $clock
     */
    public function __construct(
        ApiHelper $api,
        BuildNormalizer $buildNormalizer,
        PushNormalizer $pushNormalizer,
        BuildRepository $buildRepo,
        PushRepository $pushRepo,
        Clock $clock
    ) {
        $this->api = $api;
        $this->buildNormalizer = $buildNormalizer;
        $this->pushNormalizer = $pushNormalizer;
        $this->buildRepo = $buildRepo;
        $this->pushRepo = $pushRepo;
        $this->clock = $clock;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @param callable $notFound
     * @return null
     */
    public function __invoke(Request $request, Response $response, array $params = [])
    {
        $since = $request->get('since');
        $createdAfter = null;
        if ($since && !$createdAfter = $this->parseValidSinceTime($since)) {
            // @todo need to use problem+json
            $response->setBody('Malformed Datetime! Dates must be ISO8601 UTC.');
            return $response->setStatus(400);
        }

        $createdAfter = $createdAfter ?: $this->getDefaultSinceTime();

        if (!$jobs = $this->retrieveJobs($createdAfter)) {
            return $response->setStatus(404);
        }

        $jobs = $this->formatQueue($jobs);

        $this->api->prepareResponse(
            $response,
            [
                'self' => ['href' => ['api.queue', []], 'type' => 'Queue'],
            ],
            $jobs
        );
    }

    /**
     * @param Build[]|Push[] $queue
     * @return array
     */
    private function formatQueue(array $queue)
    {
        $normalizedQueue = [];

        $criteria = [
            'build' => [
                'environment' => [],
                'repository' => []
            ],
            'deployment' => ['server' => []]
        ];

        foreach ($queue as $job) {
            if ($job instanceof Push) {
                $normalized = $this->pushNormalizer->normalize($job, $criteria);
                $normalized = array_merge([
                    // i need dis
                    'uniqueId' => 'push-' . $normalized['id'],
                    'type' => 'push'
                ], $normalized);

            } else {
                $normalized = $this->buildNormalizer->normalize($job, $criteria['build']);
                $normalized = array_merge([
                    // i need dis
                    'uniqueId' => 'build-' . $normalized['id'],
                    'type' => 'build'
                ], $normalized);
            }

            $normalizedQueue[] = $normalized;
        }

        return $normalizedQueue;
    }

    /**
     * If no filter is specified, only get builds created in the last 20 minutes.
     *
     * @return TimePoint
     */
    private function getDefaultSinceTime()
    {
        $time = $this->clock->read();
        return $time->modify('-20 minutes');
    }

    /**
     * @param string $since
     * @return TimePoint
     */
    private function parseValidSinceTime($since)
    {
        if (!$date = DateTime::createFromFormat(DateTime::ISO8601, $since, new DateTimeZone('UTC'))) {
            return null;
        }

        return new TimePoint(
            $date->format('Y'),
            $date->format('m'),
            $date->format('d'),
            $date->format('H'),
            $date->format('i'),
            $date->format('s'),
            'UTC'
        );
    }

    /**
     * @param string $since
     * @return array
     */
    private function retrieveJobs($since)
    {
        $buildCriteria = (new Criteria)
            ->where(Criteria::expr()->gte('created', $since))
            ->orderBy(['created' => 'DESC']);

        $pushCriteria = (new Criteria)
            ->where(Criteria::expr()->gte('created', $since))
            ->orderBy(['created' => 'DESC']);

        $builds = $this->buildRepo->matching($buildCriteria);
        $pushes = $this->pushRepo->matching($pushCriteria);

        $jobs = array_merge($builds->toArray(), $pushes->toArray());
        usort($jobs, $this->queueSort());

        return $jobs;
    }

    /**
     * @return Closure
     */
    private function queueSort()
    {
        return function($aEntity, $bEntity) {
            $a = $aEntity->getCreated();
            $b = $bEntity->getCreated();

            if ($a == $b) {
                return 0;
            }

            // If missing created time, move to bottom
            if ($a === null xor $b === null) {
                return ($a === null) ? 1 : 0;
            }

            if ($a < $b) {
                return 1;
            }

            return -1;
        };
    }
}
