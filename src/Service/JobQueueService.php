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
use Hal\Core\Entity\Build;
use Hal\Core\Entity\Release;
use Hal\Core\Type\JobStatusEnum;
use QL\MCP\Common\Time\Clock;
use QL\MCP\Common\Time\TimePoint;

class JobQueueService
{
    /**
     * @var EntityRepository
     */
    private $buildRepo;
    private $releaseRepo;

    /**
     * @var Clock
     */
    private $clock;

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em, Clock $clock)
    {
        $this->buildRepo = $em->getRepository(Build::class);
        $this->releaseRepo = $em->getRepository(Release::class);

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

        $buildCriteria = $this->getCriteria($runningStatuses);
        $releaseCriteria = $this->getCriteria($runningStatuses);

        return $this->getJobs($buildCriteria, $releaseCriteria);
    }

    /**
     * @return array
     */
    public function getStuckJobs()
    {
        $before = $this->clock
            ->read()
            ->modify('-60 minutes');

        $runningStatuses = [
            JobStatusEnum::TYPE_PENDING,
            JobStatusEnum::TYPE_RUNNING
        ];

        $buildCriteria = $this->getCriteria($runningStatuses, $before);
        $releaseCriteria = $this->getCriteria($runningStatuses, $before);

        return $this->getJobs($buildCriteria, $releaseCriteria);
    }

    /**
     * @param TimePoint $after
     * @param TimePoint|null $before
     *
     * @return array
     */
    public function getHistory(TimePoint $after, ?TimePoint $before)
    {
        $buildCriteria = $this->getCriteria([], $before, $after);
        $releaseCriteria = $this->getCriteria([], $before, $after);

        return $this->getJobs($buildCriteria, $releaseCriteria);
    }

    /**
     * @return bool
     */
    public function isToday($from)
    {
        $now = $this->clock->read();
        $isToday = $now->format('Y-m-d', 'UTC') === $from->format('Y-m-d', 'UTC');

        return $isToday;
    }

    /**
     * @param string|null $date
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

    /**
     * @param Criteria $buildCriteria
     * @param Criteria $releaseCriteria
     *
     * @return array
     */
    private function getJobs(Criteria $buildCriteria, Criteria $releaseCriteria)
    {
        $builds = $this->buildRepo->matching($buildCriteria);
        $releases = $this->releaseRepo->matching($releaseCriteria);

        $jobs = array_merge($builds->toArray(), $releases->toArray());
        usort($jobs, $this->queueSort());

        return $jobs;
    }

    /**
     * @return Closure
     */
    private function queueSort()
    {
        return function ($aEntity, $bEntity) {
            $a = $aEntity->created();
            $b = $bEntity->created();

            if ($a == $b) {
                return 0;
            }

            if ($a < $b) {
                return 1;
            }

            return -1;
        };
    }
}
