<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Services;

use Doctrine\Common\Collections\Criteria;
use MCP\Cache\CachingTrait;
use MCP\DataType\Time\Clock;
use MCP\DataType\Time\TimePoint;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Repository\BuildRepository;
use QL\Hal\Core\Repository\PushRepository;

class StatsService
{
    use CachingTrait;

    const KEY_STATS = 'stats:totals.%s';

    /**
     * @type BuildRepository
     */
    private $buildRepo;

    /**
     * @type PushRepository
     */
    private $pushRepo;

    /**
     * @type Clock
     */
    private $clock;

    /**
     * @type string
     */
    private $timezone;

    /**
     * @param BuildRepository $buildRepo
     * @param PushRepository $pushRepo
     * @param Clock $clock
     * @param string $timezone
     */
    public function __construct(BuildRepository $buildRepo, PushRepository $pushRepo, Clock $clock, $timezone)
    {
        $this->buildRepo = $buildRepo;
        $this->pushRepo = $pushRepo;
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

        $data = $this->getRepoStatsForRange($from, $to);

        $this->setToCache($key, $data);
        return $data;
    }

    /**
     * @param Environment $environment
     *
     * @return bool
     */
    private function isProduction(Environment $environment)
    {
        $env = $environment->getKey();

        // starts with prod = prod
        if (stripos($env, 'prod') !== false) {
            return true;
        }

        return false;
    }

    /**
     * @param string $from
     * @param string $to
     *
     * @return array
     */
    private function getRepoStatsForRange($from, $to)
    {
        $data = [
            'builds' => 0,
            'prod_builds' => 0,
            'pushes' => 0,
            'prod_pushes' => 0,
            'active_repos' => 0,
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
        $repos = [];
        $prodBuilds = 0;
        $prodPushes = 0;

        foreach ($builds as $build) {
            $repoId = $build->getRepository()->getId();
            $env = $build->getEnvironment()->getKey();

            $repos[$repoId] = true;

            if ($this->isProduction($build->getEnvironment())) {
                $prodBuilds++;
            }
        }

        foreach ($pushes as $push) {
            $repoId = $push->getRepository()->getId();
            $env = $push->getDeployment()->getServer()->getEnvironment();

            $repos[$repoId] = true;

            if ($this->isProduction($env)) {
                $prodPushes++;
            }
        }

        return [
            'prod_builds' => $prodBuilds,
            'prod_pushes' => $prodPushes,
            'active_repos' => count($repos)
        ];
    }
}
