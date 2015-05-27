<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Api\Normalizer\BuildNormalizer;
use QL\Hal\Api\Normalizer\PushNormalizer;
use QL\Hal\Api\Utility\HypermediaLinkTrait;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Push;
use QL\HttpProblem\HttpProblemException;
use QL\Panthor\ControllerInterface;

/**
 * Get the current status of one or more jobs.
 */
class QueueRefreshController implements ControllerInterface
{
    use HypermediaResourceTrait;
    use HypermediaLinkTrait;

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
     * @type array
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
     * @throws HttpProblemException
     */
    public function __invoke()
    {
        if (!isset($this->parameters['jobs'])) {
            // Need some kind of error messaging
            throw HttpProblemException::build(400, 'missing-jobs');
        }

        $identifiers = explode(' ', $this->parameters['jobs']);

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
                'self' => $this->buildLink(['api.queue.refresh', ['jobs' => implode('+', $identifiers)]])
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
