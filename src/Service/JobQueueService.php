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
use QL\MCP\Common\Time\Clock;
use QL\MCP\Common\Time\TimePoint;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Push;

class JobQueueService
{
    /**
     * @var EntityRepository
     */
    private $buildRepo;
    private $pushRepo;

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
        $this->pushRepo = $em->getRepository(Push::class);

        $this->clock = $clock;
    }

    /**
     * @return array
     */
    public function getPendingJobs()
    {
        $buildCriteria = $this->getCriteria(['Waiting', 'Building']);
        $pushCriteria = $this->getCriteria(['Waiting', 'Pushing']);

        return $this->getJobs($buildCriteria, $pushCriteria);
    }

    /**
     * @return array
     */
    public function getStuckJobs()
    {
        $before = $this->clock
            ->read()
            ->modify('-60 minutes');

        $buildCriteria = $this->getCriteria(['Waiting', 'Building'], $before);
        $pushCriteria = $this->getCriteria(['Waiting', 'Pushing'], $before);

        return $this->getJobs($buildCriteria, $pushCriteria);
    }

    /**
     * @param Timepoint $after
     * @param Timepoint $before
     *
     * @return array
     */
    public function getHistory(TimePoint $after, TimePoint $before)
    {
        $buildCriteria = $this->getCriteria([], $before, $after);
        $pushCriteria = $this->getCriteria([], $before, $after);

        return $this->getJobs($buildCriteria, $pushCriteria);
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
     * @param Criteria $pushCriteria
     *
     * @return array
     */
    private function getJobs(Criteria $buildCriteria, Criteria $pushCriteria)
    {
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
            $a = $aEntity->created();
            $b = $bEntity->created();

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
