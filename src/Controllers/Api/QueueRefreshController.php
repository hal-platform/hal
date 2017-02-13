<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Api;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Api\Hyperlink;
use Hal\UI\Api\ResponseFormatter;
use Hal\UI\Api\Normalizer\BuildNormalizer;
use Hal\UI\Api\Normalizer\PushNormalizer;
use Hal\UI\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Push;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Exception\HTTPProblemException;

/**
 * Get the current status of one or more jobs.
 */
class QueueRefreshController implements ControllerInterface
{
    use HypermediaResourceTrait;

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
     * @var array
     */
    private $parameters;

    /**
     * @param ResponseFormatter $formatter
     * @param BuildNormalizer $buildNormalizer
     * @param PushNormalizer $pushNormalizer
     * @param EntityManagerInterface $em
     * @param array $parameters
     */
    public function __construct(
        ResponseFormatter $formatter,
        BuildNormalizer $buildNormalizer,
        PushNormalizer $pushNormalizer,
        EntityManagerInterface $em,
        array $parameters
    ) {
        $this->formatter = $formatter;
        $this->buildNormalizer = $buildNormalizer;
        $this->pushNormalizer = $pushNormalizer;
        $this->buildRepo = $em->getRepository(Build::CLASS);
        $this->pushRepo = $em->getRepository(Push::CLASS);
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     * @throws HTTPProblemException
     */
    public function __invoke()
    {
        if (!isset($this->parameters['jobs'])) {
            // Need some kind of error messaging
            throw new HTTPProblemException(400, 'Missing jobs - No jobs to update');
        }

        $identifiers = explode(' ', $this->parameters['jobs']);

        if (count($identifiers) > 50) {
            throw new HTTPProblemException(400, 'Cannot get the status of more than 100 jobs at once.');
        }

        $jobs = $this->retrieveJobs($identifiers);
        $status = (count($jobs) > 0) ? 200 : 404;

        $this->formatter->respond($this->buildResource(
            [
                'count' => count($jobs)
            ],
            [
                'jobs' => $this->formatQueue($jobs)
            ],
            [
                'self' => new Hyperlink(['api.queue.refresh', ['jobs' => implode('+', $identifiers)]])
            ]
        ), $status);
    }

    /**
     * Filter out a specific types of jobs from a combined list of push and build IDs
     *
     * @param array $identifiers
     * @param string $type
     * @return array
     */
    private function filterIdentifiers(array $identifiers, $type)
    {
        if ($type === 'build') {
            $prefix = 'b';

        } elseif ($type === 'push') {
            $prefix = 'p';

        } else {
            // Return empty list if no valid type provided
            return [];
        }

        return array_filter($identifiers, function ($v) use ($prefix) {
            return (substr($v, 0, 1) === $prefix);
        });
    }

    /**
     * @param Build[]|Push[] $queue
     * @return array
     */
    private function formatQueue(array $queue)
    {
        $normalizedQueue = [];

        foreach ($queue as $job) {

            if ($job instanceof Push) {
                $normalizedQueue[] = $this->pushNormalizer->resource($job);

            } elseif ($job instanceof Build) {
                $normalizedQueue[] = $this->buildNormalizer->resource($job);
            }
        }

        return $normalizedQueue;
    }

    /**
     * @param array $identifiers
     * @return array
     */
    private function retrieveJobs($identifiers)
    {
        $builds = $pushes = [];

        if ($buildIds = $this->filterIdentifiers($identifiers, 'build')) {
            $buildCriteria = (new Criteria)
                ->where(Criteria::expr()->in('id', $buildIds));
            $builds = $this->buildRepo->matching($buildCriteria);
            $builds = $builds->toArray();
        }

        if ($pushIds = $this->filterIdentifiers($identifiers, 'push')) {
            $pushCriteria = (new Criteria)
                ->where(Criteria::expr()->in('id', $pushIds));
            $pushes = $this->pushRepo->matching($pushCriteria);
            $pushes = $pushes->toArray();
        }

        return array_merge($builds, $pushes);
    }
}
