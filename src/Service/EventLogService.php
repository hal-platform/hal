<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Service;

use DateTime;
use DateTimeZone;
use MCP\DataType\Time\TimePoint;
use Predis\Client as Predis;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\EventLog;
use QL\Hal\Core\Entity\Push;
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
     * @type Predis
     */
    private $predis;

    /**
     * @type Json
     */
    private $json;

    /**
     * @param Predis $predis
     * @param Json $json
     */
    public function __construct(Predis $predis, Json $json)
    {
        $this->predis = $predis;
        $this->json = $json;
    }

    /**
     * @param Build|Push|null $job
     *
     * @return EventLog[]|null
     */
    public function getLogs($job)
    {
        if (!$job instanceof Build && !$job instanceof Push) {
            return [];
        }

        // if finished, get logs from db
        if (in_array($job->status(), ['Success', 'Error', 'Removed'], true)) {
            return $job->logs()->toArray();
        }

        return $this->getFromRedis($job);
    }

    /**
     * @param Build|Push $job
     *
     * @return EventLog[]|null
     */
    public function getFromRedis($job)
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

                if ($job instanceof Build) {
                    $log->withBuild($job);
                }

                if ($job instanceof Push) {
                    $log->withPush($job);
                }
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
     * @return EventLog
     */
    private function derez(array $data)
    {
        $data = array_replace([
            'id' => '',
            'event' => '',
            'order' => 0,
            'created' => null,
            'message' => '',
            'status' => '',
            'build' => null,
            'push' => null,
        ], $data);

        $log = (new Eventlog)
            ->withId($data['id'])
            ->withEvent($data['event'])
            ->withOrder($data['order'])
            ->withMessage($data['message'])
            ->withStatus($data['status']);

        if ($timepoint = $this->buildTimepoint($data['created'])) {
            $log->withCreated($timepoint);
        }

        return $log;
    }

    /**
     * @param string $time
     *
     * @return TimePoint|null
     */
    private function buildTimepoint($time)
    {
        if (!$date = DateTime::createFromFormat(DateTime::RFC3339, $time, new DateTimeZone('UTC'))) {
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
}
