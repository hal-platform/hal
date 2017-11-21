<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Predis\Client as Predis;
use Hal\Core\Entity\Build;
use Hal\Core\Entity\JobEvent;
use Hal\Core\Entity\Release;
use QL\MCP\Common\Time\Clock;
use QL\MCP\Common\Time\TimePoint;
use QL\Panthor\Utility\Json;

/**
 * This service will retrieve event logs from redis if they are available.
 *
 * The agents push logs (without context) to redis, so the logs can be read from the frontend while job are in progress.
 */
class EventLogService
{
    const REDIS_LOG_KEY = 'event-logs:%s';

    /**
     * @var Predis
     */
    private $predis;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var Clock
     */
    private $clock;

    /**
     * @var EntityRepository
     */
    private $eventlogsRepository;

    /**
     * @param Predis $predis
     * @param Json $json
     * @param Clock $clock
     */
    public function __construct(Predis $predis, Json $json, Clock $clock, EntityManagerInterface $em)
    {
        $this->predis = $predis;
        $this->json = $json;
        $this->clock = $clock;
        $this->eventlogsRepository = $em->getRepository(JobEvent::class);
    }

    /**
     * @param Build|Release|null $job
     *
     * @return JobEvent[]|null
     */
    public function getLogs($job): ?array
    {
        if (!$job instanceof Build && !$job instanceof Release) {
            return [];
        }

        // if finished, get logs from db
        if (in_array($job->status(), ['success', 'failure', 'removed'], true)) {
            return $this->eventlogsRepository->findBy(['parent' => $job->id()]);
        }

        return $this->getFromRedis($job);
    }

    /**
     * @param Build|Release $job
     *
     * @return JobEvent[]|null
     */
    public function getFromRedis($job): ?array
    {
        $key = sprintf(self::REDIS_LOG_KEY, $job->id());

        $data = $this->predis->lrange($key, 0, -1);

        if (!$data) {
            return [];
        }

        $logs = [];
        foreach ($data as $json) {
            $decoded = $this->json->decode($json);

            if (is_array($decoded)) {
                $log = $this->derez($decoded);
                $logs[] = $log;
                $log->withParentID($job->id());
            }
        }

        usort($logs, function($a, $b) {
            return ($a->order() > $b->order()) ? 1 : -1;
        });

        return $logs;
    }

    /**
     * @param array $data
     *
     * @return JobEvent
     */
    private function derez(array $data)
    {
        $data = array_replace([
            'id' => '',
            'stage' => '',
            'order' => 0,
            'created' => null,
            'message' => '',
            'status' => '',
            'parent_id' => null,
        ], $data);

        $log = (new JobEvent)
            ->withId($data['id'])
            ->withstage($data['event'])
            ->withOrder($data['order'])
            ->withMessage($data['message'])
            ->withStatus($data['status']);

        if ($timepoint = $this->clock->fromString($data['created'])) {
            $log->withCreated($timepoint);
        }

        return $log;
    }
}
