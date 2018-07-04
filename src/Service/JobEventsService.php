<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\Job;
use Hal\Core\Entity\Job\JobEvent;
use Predis\Client as Predis;
use QL\MCP\Common\Clock;
use QL\Panthor\Utility\JSON;

/**
 * This service will retrieve event logs from redis if they are available.
 *
 * The agents push logs (without context) to redis, so the logs can be read from the frontend while job are in progress.
 */
class JobEventsService
{
    const REDIS_LOG_KEY = 'event-logs:%s';

    /**
     * @var Predis
     */
    private $predis;

    /**
     * @var EntityRepository
     */
    private $eventsRepo;

    /**
     * @var JSON
     */
    private $json;

    /**
     * @var Clock
     */
    private $clock;

    /**
     * @param Predis $predis
     * @param JSON $json
     * @param Clock $clock
     */
    public function __construct(EntityManagerInterface $em, Predis $predis, JSON $json, Clock $clock)
    {
        $this->predis = $predis;
        $this->eventsRepo = $em->getRepository(JobEvent::class);

        $this->json = $json;
        $this->clock = $clock;
    }

    /**
     * @param Job $job
     *
     * @return JobEvent[]|null
     */
    public function getEvents(Job $job): ?array
    {
        // if finished, get logs from db
        if ($job->isFinished()) {
            return $this->eventsRepo->findBy(['job' => $job], ['order' => 'ASC']);
        }

        return $this->getFromRedis($job);
    }

    /**
     * @param Job $job
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

        $events = [];
        foreach ($data as $json) {
            $decoded = $this->json->decode($json);

            if (is_array($decoded)) {
                $event = $this
                    ->derez($decoded)
                    ->withJob($job);

                $events[] = $event;
            }
        }

        usort($events, function ($a, $b) {
            return ($a->order() > $b->order()) ? 1 : -1;
        });

        return $events;
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
            'created' => null,
            'stage' => '',
            'status' => '',
            'order' => 0,
            'message' => '',
        ], $data);

        if ($timepoint = $this->clock->fromString($data['created'])) {
            $data['created'] = $timepoint;
        }

        $event = (new JobEvent($data['id'], $data['created']))
            ->withStage($data['stage'])
            ->withStatus($data['status'])
            ->withOrder($data['order'])
            ->withMessage($data['message']);

        return $event;
    }
}
