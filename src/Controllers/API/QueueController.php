<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\API;

use Hal\UI\API\Normalizer\BuildNormalizer;
use Hal\UI\API\Normalizer\ReleaseNormalizer;
use Hal\UI\API\Hyperlink;
use Hal\UI\API\HypermediaResource;
use Hal\UI\API\ResponseFormatter;
use Hal\UI\Controllers\APITrait;
use Hal\UI\Service\JobQueueService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Hal\Core\Entity\Build;
use Hal\Core\Entity\Release;
use QL\MCP\Common\Time\Clock;
use QL\MCP\Common\Time\TimePoint;
use QL\Panthor\ControllerInterface;
use QL\Panthor\HTTPProblem\ProblemRendererInterface;

/**
 * Get all pushes and builds created after the specified time.
 *
 * If no time is provided (Get param = "since"), all jobs in the past 20 minutes will be retrieved.
 *
 * @todo change "since" to use some kind of better query filtering.
 */
class QueueController implements ControllerInterface
{
    use APITrait;

    private const ERR_MALFORMED_DATE = 'Malformed Datetime! Dates must be ISO8601 UTC.';
    private const ERR_TOO_OLD = 'Invalid Datetime! The queue cannot retrieve jobs older than 3 days.';

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
    private $releaseNormalizer;

    /**
     * @var JobQueueService
     */
    private $queue;

    /**
     * @var ProblemRendererInterface
     */
    private $problemRenderer;

    /**
     * @var Clock
     */
    private $clock;

    /**
     * @param ResponseFormatter $formatter
     * @param BuildNormalizer $buildNormalizer
     * @param ReleaseNormalizer $releaseNormalizer
     * @param JobQueueService $queue
     * @param ProblemRendererInterface $problemRenderer
     * @param Clock $clock
     */
    public function __construct(
        ResponseFormatter $formatter,
        BuildNormalizer $buildNormalizer,
        ReleaseNormalizer $releaseNormalizer,
        JobQueueService $queue,
        ProblemRendererInterface $problemRenderer,
        Clock $clock
    ) {
        $this->formatter = $formatter;
        $this->buildNormalizer = $buildNormalizer;
        $this->releaseNormalizer = $releaseNormalizer;

        $this->queue = $queue;
        $this->problemRenderer = $problemRenderer;
        $this->clock = $clock;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $since = $request->getQueryParams()['since'] ?? '';
        $createdAfter = null;

        if ($since && !$createdAfter = $this->clock->fromString($since)) {
            return $this->withProblem($this->problemRenderer, $response, 400, self::ERR_MALFORMED_DATE);
        }

        $createdAfter = $createdAfter ?: $this->getDefaultSinceTime();

        $oldest = $this->clock->read()->modify('-3 days');
        if ($createdAfter->compare($oldest) !== 1) {
            return $this->withProblem($this->problemRenderer, $response, 400, self::ERR_TOO_OLD);
        }

        $jobs = $this->queue->getHistory($createdAfter, null);

        $identifiers = array_map(function($job) {
            return $job->id();
        }, $jobs);

        $links = [];
        if ($jobs) {
            $links['refresh'] = new Hyperlink(['api.queue.refresh', ['jobs' => implode('+', $identifiers)]]);
        }

        $data = [
            'count' => count($jobs)
        ];

        $resource = new HypermediaResource($data, $links, [
            'jobs' => $this->formatQueue($jobs)
        ]);

        $resource->withEmbedded(['jobs']);

        $status = (count($jobs) > 0) ? 200 : 404;
        $body = $this->formatter->buildHypermediaResponse($request, $resource);

        return $this->withHypermediaEndpoint($request, $response, $body, $status);
    }

    /**
     * @param Build[]|Release[] $queue
     *
     * @return array
     */
    private function formatQueue(array $queue)
    {
        return array_map(function ($item) {
            if ($item instanceof Release) {
                return $this->releaseNormalizer->resource($item, ['application', 'build', 'target', 'environment']);
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
}
