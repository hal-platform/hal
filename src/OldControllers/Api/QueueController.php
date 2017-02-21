<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Api;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use Hal\UI\Api\Normalizer\BuildNormalizer;
use Hal\UI\Api\Normalizer\PushNormalizer;
use Hal\UI\Api\ResponseFormatter;
use Hal\UI\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Push;
use QL\MCP\Common\Time\Clock;
use QL\MCP\Common\Time\TimePoint;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Exception\HTTPProblemException;
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
     * @var Request
     */
    private $request;

    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @var BuildNormalizer
     */
    private $buildNormalizer;

    /**
     * @var PushNormalizer
     */
    private $pushNormalizer;

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
     * @inheritDoc
     * @throws HTTPProblemException
     */
    public function __invoke()
    {
        $since = $this->request->get('since');
        $createdAfter = null;
        if ($since && !$createdAfter = $this->clock->fromString($since)) {
            throw new HTTPProblemException(400, 'Malformed Datetime! Dates must be ISO8601 UTC.');
        }

        $createdAfter = $createdAfter ?: $this->getDefaultSinceTime();

        $oldest = $this->clock->read()->modify('-3 days');
        if ($createdAfter->compare($oldest) !== 1) {
            throw new HTTPProblemException(400, 'Invalid Datetime! The queue cannot retrieve jobs older than 3 days.');
        }

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
