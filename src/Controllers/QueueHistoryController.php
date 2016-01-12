<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Controllers;

use Closure;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Push;
use QL\MCP\Common\Time\Clock;
use QL\MCP\Common\Time\TimePoint;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class QueueHistoryController implements ControllerInterface
{
    const MAX_PER_PAGE = 25;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityRepository
     */
    private $buildRepo;
    private $pushRepo;

    /**
     * @var array
     */
    private $parameters;

    /**
     * @var Clock
     */
    private $clock;

    /**
     * @var string
     */
    private $timezone;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param Clock $clock
     * @param array $parameters
     * @param string $timezone
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        Clock $clock,
        array $parameters,
        $timezone
    ) {
        $this->template = $template;
        $this->buildRepo = $em->getRepository(Build::CLASS);
        $this->pushRepo = $em->getRepository(Push::CLASS);

        $this->parameters = $parameters;

        $this->clock = $clock;
        $this->timezone = $timezone;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $times = $this->getTimespan();

        $now = $this->clock->read();
        $isToday = $now->format('Y-m-d', 'UTC') === $times[0]->format('Y-m-d', 'UTC');

        $this->template->render([
            'is_today' => $isToday,
            'selected_date' => $times[0],
            'pending' => $this->getHistory($times[0], $times[1])
        ]);
    }

    /**
     * @param int $page
     *
     * @return array
     */
    private function getHistory(TimePoint $from, TimePoint $to)
    {
        $criteria = (new Criteria)
            ->where(Criteria::expr()->gte('created', $from))
            ->andWhere(Criteria::expr()->lte('created', $to))
            ->orderBy(['created' => 'DESC']);

        $builds = $this->buildRepo
            ->matching($criteria)
            ->toArray();

        $pushes = $this->pushRepo
            ->matching($criteria)
            ->toArray();

        $jobs = array_merge($builds, $pushes);
        usort($jobs, $this->queueSort());

        return $jobs;
    }

    /**
     * @return array
     */
    private function getTimespan()
    {
        $date = null;
        if (isset($this->parameters['date'])) {
            $date = $this->clock->fromString($this->parameters['date'], 'Y-m-d');
        }

        if (!$date) {
            $date = $this->clock->read();
        }

        $y = $date->format('Y', $this->timezone);
        $m = $date->format('m', $this->timezone);
        $d = $date->format('d', $this->timezone);

        $from = new TimePoint($y, $m, $d, 0, 0, 0, $this->timezone);
        $to = new TimePoint($y, $m, $d, 23, 59, 59, $this->timezone);

        return [$from, $to];
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
