<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api;

use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use MCP\DataType\Time\Clock;
use MCP\DataType\Time\TimePoint;
use QL\Hal\Api\Normalizer\BuildNormalizer;
use QL\Hal\Api\Normalizer\PushNormalizer;
use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Push;
use QL\HttpProblem\HttpProblemException;
use QL\Panthor\ControllerInterface;
use Slim\Http\Request;

/**
 * Get all pushes and builds created after the specified time.
 *
 * If no time is provided (Get param = "since"), all jobs in the past 20 minutes will be retrieved.
 * @todo change "since" to use some kind of better query filtering.
 */
class QueueController implements ControllerInterface
{
    use HypermediaResourceTrait;

    /**
     * @type Request
     */
    private $request;

    /**
     * @type ResponseFormatter
     */
    private $formatter;

    /**
     * @type BuildNormalizer
     */
    private $buildNormalizer;

    /**
     * @type PushNormalizer
     */
    private $pushNormalizer;

    /**
     * @type EntityRepository
     */
    private $buildRepo;
    private $pushRepo;

    /**
     * @type Clock
     */
    private $clock;

    /**
     * @param Request $request
     * @param ResponseFormatter $formatter
     * @param BuildNormalizer $buildNormalizer
     * @param PushNormalizer $pushNormalizer
     * @param EntityManagerInterface $em
     * @param Clock $clock
     */
    public function __construct(
        Request $request,
        ResponseFormatter $formatter,
        BuildNormalizer $buildNormalizer,
        PushNormalizer $pushNormalizer,
        EntityManagerInterface $em,
        Clock $clock
    ) {
        $this->request = $request;
        $this->formatter = $formatter;
        $this->buildNormalizer = $buildNormalizer;
        $this->pushNormalizer = $pushNormalizer;
        $this->buildRepo = $em->getRepository(Build::CLASS);
        $this->pushRepo = $em->getRepository(Push::CLASS);
        $this->clock = $clock;
    }

    /**
     * {@inheritdoc}
     * @throws HttpProblemException
     */
    public function __invoke()
    {
        $since = $this->request->get('since');
        $createdAfter = null;
        if ($since && !$createdAfter = $this->parseValidSinceTime($since)) {
            throw HttpProblemException::build(400, 'Malformed Datetime! Dates must be ISO8601 UTC.');
        }

        $createdAfter = $createdAfter ?: $this->getDefaultSinceTime();
        $jobs = $this->retrieveJobs($createdAfter);
        $status = count($jobs) > 0 ? 200 : 404;

        $this->formatter->respond($this->buildResource(
            [
                'count' => count($jobs)
            ],
            [
                'jobs' => $this->formatQueue($jobs)
            ],
            []
        ), $status);
    }

    /**
     * @param Build[]|Push[] $queue
     * @return array
     */
    private function formatQueue(array $queue)
    {
        return array_map(function ($item) {
            if ($item instanceof Push) {
                return $this->pushNormalizer->resource($item, ['build', 'deployment', 'application']);
            }

            if ($item instanceof Build) {
                return $this->buildNormalizer->resource($item, ['application']);
            }
        }, $queue);
    }

    /**
     * If no filter is specified, only get builds created in the last 20 minutes.
     *
     * @return TimePoint
     */
    private function getDefaultSinceTime()
    {
        $time = $this->clock->read();
        return $time->modify('-20 minutes');
    }

    /**
     * @param string $since
     * @return TimePoint
     */
    private function parseValidSinceTime($since)
    {
        if (!$date = DateTime::createFromFormat(DateTime::ISO8601, $since, new DateTimeZone('UTC'))) {
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

    /**
     * @param string $since
     * @return array
     */
    private function retrieveJobs($since)
    {
        $buildCriteria = (new Criteria)
            ->where(Criteria::expr()->gte('created', $since))
            ->orderBy(['created' => 'DESC']);

        $pushCriteria = (new Criteria)
            ->where(Criteria::expr()->gte('created', $since))
            ->orderBy(['created' => 'DESC']);

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
