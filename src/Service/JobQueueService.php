<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Service;

use Closure;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\Job;
use Hal\Core\Type\JobStatusEnum;
use QL\MCP\Common\Time\Clock;
use QL\MCP\Common\Time\TimePoint;

class JobQueueService
{
    private const HELP_IM_STUCK_IF_OVER = '-60 minutes';

    /**
     * @var EntityRepository
     */
    private $jobRepo;

    /**
     * @var Clock
     */
    private $clock;

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em, Clock $clock)
    {
        $this->jobRepo = $em->getRepository(Job::class);

        $this->clock = $clock;
    }

    /**
     * @return array
     */
    public function getPendingJobs()
    {
        $runningStatuses = [
            JobStatusEnum::TYPE_PENDING,
            JobStatusEnum::TYPE_RUNNING
        ];

        $criteria = $this->getCriteria($runningStatuses);

        return $this->jobRepo
            ->matching($criteria)
            ->toArray();
    }

    /**
     * @return array
     */
    public function getStuckJobs()
    {
        $before = $this->clock
            ->read()
            ->modify(self::HELP_IM_STUCK_IF_OVER);

        $runningStatuses = [
            JobStatusEnum::TYPE_PENDING,
            JobStatusEnum::TYPE_RUNNING
        ];

        $criteria = $this->getCriteria($runningStatuses, $before);

        return $this->jobRepo
            ->matching($criteria)
            ->toArray();
    }

    /**
     * @param TimePoint $after
     * @param TimePoint|null $before
     *
     * @return array
     */
    public function getHistory(TimePoint $after, ?TimePoint $before)
    {
        $criteria = $this->getCriteria([], $before, $after);

        return $this->jobRepo
            ->matching($criteria)
            ->toArray();
    }

    /**
     * @param TimePoint $time
     *
     * @return bool
     */
    public function isToday(TimePoint $time)
    {
        $now = $this->clock->read();
        $isToday = $now->format('Y-m-d', 'UTC') === $time->format('Y-m-d', 'UTC');

        return $isToday;
    }

    /**
     * @param string $date
     * @param string $timezone
     *
     * @return array
     */
    public function getTimeRange($date = '', $timezone = 'UTC')
    {
        if ($date) {
            $date = $this->clock->fromString($date, 'Y-m-d');
        }

        if (!$date) {
            $date = $this->clock->read();
        }

        $y = $date->format('Y', $timezone);
        $m = $date->format('m', $timezone);
        $d = $date->format('d', $timezone);

        $from = new TimePoint($y, $m, $d, 0, 0, 0, $timezone);
        $to = new TimePoint($y, $m, $d, 23, 59, 59, $timezone);

        return [$from, $to];
    }

    /**
     * @param array $statuses
     * @param TimePoint|null $before
     * @param TimePoint|null $after
     *
     * @return array
     */
    private function getCriteria(array $statuses = [], TimePoint $before = null, TimePoint $after = null)
    {
        $criteria = new Criteria(null, ['created' => 'DESC']);

        foreach ($statuses as $status) {
            $criteria = $criteria->orWhere(Criteria::expr()->eq('status', $status));
        }

        if ($before) {
            $criteria = $criteria->andWhere(Criteria::expr()->lte('created', $before));
        }

        if ($after) {
            $criteria = $criteria->andWhere(Criteria::expr()->gte('created', $after));
        }

        return $criteria;
    }
}
