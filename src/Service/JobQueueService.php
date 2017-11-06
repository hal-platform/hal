<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Service;

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

        $buildCriteria = $this->getCriteria($runningStatuses, $before);
        $releaseCriteria = $this->getCriteria($runningStatuses, $before);

        return $this->getJobs($buildCriteria, $releaseCriteria);
    }

    /**
     * @param Timepoint $after
     * @param Timepoint|null $before
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
        return function($aEntity, $bEntity) {
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
