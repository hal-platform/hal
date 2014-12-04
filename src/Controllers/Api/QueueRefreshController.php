<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api;

use Doctrine\Common\Collections\Criteria;
use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Api\Normalizer\BuildNormalizer;
use QL\Hal\Api\Normalizer\PushNormalizer;
use QL\Hal\Api\Utility\HypermediaLinkTrait;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Entity\Repository\BuildRepository;
use QL\Hal\Core\Entity\Repository\PushRepository;
use QL\HttpProblem\HttpProblemException;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Get the current status of one or more jobs.
 */
class QueueRefreshController
{
    use HypermediaResourceTrait;
    use HypermediaLinkTrait;

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
     * @var BuildRepository
     */
    private $buildRepo;

    /**
     * @var PushRepository
     */
    private $pushRepo;

    /**
     * @param ResponseFormatter $formatter
     * @param BuildNormalizer $buildNormalizer
     * @param PushNormalizer $pushNormalizer
     * @param BuildRepository $buildRepo
     * @param PushRepository $pushRepo
     */
    public function __construct(
        ResponseFormatter $formatter,
        BuildNormalizer $buildNormalizer,
        PushNormalizer $pushNormalizer,
        BuildRepository $buildRepo,
        PushRepository $pushRepo
    ) {
        $this->formatter = $formatter;
        $this->buildNormalizer = $buildNormalizer;
        $this->pushNormalizer = $pushNormalizer;
        $this->buildRepo = $buildRepo;
        $this->pushRepo = $pushRepo;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @throws HttpProblemException
     */
    public function __invoke(Request $request, Response $response, array $params = [])
    {
        if (!isset($params['jobs'])) {
            // Need some kind of error messaging
            throw HttpProblemException::build(400, 'missing-uniqueId');
        }

        $identifiers = explode(' ', $params['jobs']);

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
