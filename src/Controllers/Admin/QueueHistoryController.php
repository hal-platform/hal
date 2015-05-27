<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Admin;

use Closure;
use DateTime;
use DateTimeZone;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use MCP\DataType\Time\Clock;
use MCP\DataType\Time\TimePoint;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Push;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Response;

class QueueHistoryController implements ControllerInterface
{
    const MAX_PER_PAGE = 25;

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type EntityRepository
     */
    private $buildRepo;
    private $pushRepo;

    /**
     * @type Response
     */
    private $response;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @type Clock
     */
    private $clock;

    /**
     * @type string
     */
    private $timezone;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param Response $response
     * @param Clock $clock
     * @param array $parameters
     * @param string $timezone
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        Response $response,
        Clock $clock,
        array $parameters,
        $timezone
    ) {
        $this->template = $template;
        $this->buildRepo = $em->getRepository(Build::CLASS);
        $this->pushRepo = $em->getRepository(Push::CLASS);

        $this->response = $response;
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

        $rendered = $this->template->render([
            'selected_date' => $times[0],
            'pending' => $this->getHistory($times[0], $times[1])
        ]);

        $this->response->setBody($rendered);
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
            $date = $this->parseValidDate($this->parameters['date']);
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
     * @param string $date
     *
     * @return TimePoint
     */
    private function parseValidDate($date)
    {
        if (!$date = DateTime::createFromFormat('Y-m-d', $date, new DateTimeZone($this->timezone))) {
            return null;
        }

        return new TimePoint(
            $date->format('Y'),
            $date->format('m'),
            $date->format('d'),
            0,
            0,
            0,
            $this->timezone
        );
    }

    /**
     * @return Closure
     */
    private function queueSort()
    {
        return function($aEntity, $bEntity) {
            $a = $aEntity->getCreated();
            $b = $bEntity->getCreated();

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
