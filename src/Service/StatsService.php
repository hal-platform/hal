<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Service;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use MCP\Cache\CachingTrait;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Entity\Environment;
use QL\MCP\Common\Time\Clock;
use QL\MCP\Common\Time\TimePoint;

class StatsService
{
    use CachingTrait;

    const KEY_STATS = 'stats:totals.%s';

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
     * @var string
     */
    private $timezone;

    /**
     * @param EntityManagerInterface $em
     * @param Clock $clock
     * @param string $timezone
     */
    public function __construct(EntityManagerInterface $em, Clock $clock, $timezone)
    {
        $this->buildRepo = $em->getRepository(Build::CLASS);
        $this->pushRepo = $em->getRepository(Push::CLASS);
        $this->clock = $clock;
        $this->timezone = $timezone;
    }

    /**
     * @return array
     */
    public function getStatsForToday()
    {
        $now = $this->clock->read();
        $y = $now->format('Y', $this->timezone);
        $m = $now->format('m', $this->timezone);
        $d = $now->format('d', $this->timezone);

        $from = new TimePoint($y, $m, $d, 0, 0, 0, $this->timezone);
        $to = new TimePoint($y, $m, $d, 23, 59, 59, $this->timezone);

        return $this->getStatsForRange($from, $to);
    }

    /**
     * @param TimePoint $from
     * @param TimePoint $to
     *
     * @return array
     */
    public function getStatsForRange(TimePoint $from, TimePoint $to)
    {
        $hash = md5($from->format('U', $this->timezone) . $from->format('U', $this->timezone));

        $key = sprintf(self::KEY_STATS, $hash);

        if ($data = $this->getFromCache($key)) {
            return $data;
        }

        $data = $this->getApplicationStatsForRange($from, $to);

        $this->setToCache($key, $data);
        return $data;
    }

    /**
     * @param string $from
     * @param string $to
     *
     * @return array
     */
    private function getApplicationStatsForRange($from, $to)
    {
        $data = [
            'builds' => 0,
            'prod_builds' => 0,
            'pushes' => 0,
            'prod_pushes' => 0,
            'active_applications' => 0,
        ];

        $criteria = (new Criteria)
            ->where(Criteria::expr()->gte('created', $from))
            ->andWhere(Criteria::expr()->lte('created', $to));

        $builds = $this->buildRepo
            ->matching($criteria)
            ->toArray();

        $pushes = $this->pushRepo
            ->matching($criteria)
            ->toArray();

        $data['builds'] = count($builds);
        $data['pushes'] = count($pushes);

        $jobStats = $this->collateJobStats($builds, $pushes);

        return array_replace($data, $jobStats);
    }

    /**
     * @param array $builds
     * @param array $pushes
     *
     * @return array
     */
    private function collateJobStats(array $builds, array $pushes)
    {
        $applications = [];
        $prodBuilds = 0;
        $prodPushes = 0;

        foreach ($builds as $build) {
            $applicationID = $build->application()->id();
            $env = $build->environment()->name();

            $applications[$applicationID] = true;

            if ($build->environment()->isProduction()) {
                $prodBuilds++;
            }
        }

        foreach ($pushes as $push) {
            $applicationID = $push->application()->id();
            $applications[$applicationID] = true;

            if ($push->build()->environment()->isProduction()) {
                $prodPushes++;
            }
        }

        return [
            'prod_builds' => $prodBuilds,
            'prod_pushes' => $prodPushes,
            'active_applications' => count($applications)
        ];
    }
}
