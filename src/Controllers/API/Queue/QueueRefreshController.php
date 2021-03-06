<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\API\Queue;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\JobType\Build;
use Hal\Core\Entity\JobType\Release;
use Hal\UI\API\Hyperlink;
use Hal\UI\API\HypermediaResource;
use Hal\UI\API\Normalizer\BuildNormalizer;
use Hal\UI\API\Normalizer\ReleaseNormalizer;
use Hal\UI\API\ResponseFormatter;
use Hal\UI\Controllers\APITrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\HTTPProblem\ProblemRendererInterface;

class QueueRefreshController implements ControllerInterface
{
    use APITrait;

    private const ERRT_MAX_JOBS = 'Cannot get the status of more than %s jobs at once.';
    private const MAX_JOBS = 50;

    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @var BuildNormalizer
     */
    private $buildNormalizer;

    /**
     * @var ReleaseNormalizer
     */
    private $pushNormalizer;

    /**
     * @var EntityRepository
     */
    private $buildRepo;
    private $releaseRepository;

    /**
     * @var ProblemRendererInterface
     */
    private $problemRenderer;

    /**
     * @param ResponseFormatter $formatter
     * @param BuildNormalizer $buildNormalizer
     * @param ReleaseNormalizer $pushNormalizer
     * @param EntityManagerInterface $em
     * @param ProblemRendererInterface $problemRenderer
     */
    public function __construct(
        ResponseFormatter $formatter,
        BuildNormalizer $buildNormalizer,
        ReleaseNormalizer $pushNormalizer,
        EntityManagerInterface $em,
        ProblemRendererInterface $problemRenderer
    ) {
        $this->formatter = $formatter;
        $this->buildNormalizer = $buildNormalizer;
        $this->pushNormalizer = $pushNormalizer;

        $this->buildRepo = $em->getRepository(Build::class);
        $this->releaseRepository = $em->getRepository(Release::class);

        $this->problemRenderer = $problemRenderer;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $jobs = $request
            ->getAttribute('route')
            ->getArgument('jobs');

        $identifiers = explode(' ', $jobs);

        if (count($identifiers) > self::MAX_JOBS) {
            return $this->withProblem($this->problemRenderer, $response, 400, sprintf(self::ERRT_MAX_JOBS, self::MAX_JOBS));
        }

        $jobs = $this->retrieveJobs($identifiers);

        $data = [
            'count' => count($jobs),
        ];

        $links = [
            'self' => new Hyperlink(['api.queue.refresh', ['jobs' => implode('+', $identifiers)]]),
        ];

        $resource = new HypermediaResource($data, $links, [
            'jobs' => $this->formatQueue($jobs),
        ]);

        $resource->withEmbedded(['jobs']);

        $status = (count($jobs) > 0) ? 200 : 404;
        $body = $this->formatter->buildHypermediaResponse($request, $resource);

        return $this->withHypermediaEndpoint($request, $response, $body, $status);
    }

    /**
     * Filter out a specific types of jobs from a combined list of push and build IDs
     *
     * @param array $identifiers
     * @param string $type
     *
     * @return array
     */
    private function filterIdentifiers(array $identifiers, $type)
    {
        if ($type === 'build') {
            $prefix = 'b';

        } elseif ($type === 'release') {
            $prefix = 'r';

        } else {
            // Return empty list if no valid type provided
            return [];
        }

        return array_filter($identifiers, function ($v) use ($prefix) {
            return (substr($v, 0, 1) === $prefix);
        });
    }

    /**
     * @param Build[]|Release[] $queue
     *
     * @return array
     */
    private function formatQueue(array $queue)
    {
        $normalizedQueue = [];

        foreach ($queue as $job) {
            if ($job instanceof Release) {
                $normalizedQueue[] = $this->pushNormalizer->resource($job);

            } elseif ($job instanceof Build) {
                $normalizedQueue[] = $this->buildNormalizer->resource($job);
            }
        }

        return $normalizedQueue;
    }

    /**
     * @param array $identifiers
     *
     * @return array
     */
    private function retrieveJobs($identifiers)
    {
        $builds = $releases = [];

        if ($buildIds = $this->filterIdentifiers($identifiers, 'build')) {
            $buildCriteria = (new Criteria)
                ->where(Criteria::expr()->in('id', $buildIds));
            $builds = $this->buildRepo->matching($buildCriteria);
            $builds = $builds->toArray();
        }

        if ($releaseIds = $this->filterIdentifiers($identifiers, 'release')) {
            $releaseCriteria = (new Criteria)
                ->where(Criteria::expr()->in('id', $releaseIds));
            $releases = $this->releaseRepository->matching($releaseCriteria);
            $releases = $releases->toArray();
        }

        return array_merge($builds, $releases);
    }
}
