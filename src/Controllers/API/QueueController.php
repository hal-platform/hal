<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\API;

use Hal\UI\API\Normalizer\BuildNormalizer;
use Hal\UI\API\Normalizer\PushNormalizer;
use Hal\UI\API\ResponseFormatter;
use Hal\UI\API\Utility\HypermediaResourceTrait;
use Hal\UI\Controllers\APITrait;
use Hal\UI\Service\JobQueueService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Push;
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
    use HypermediaResourceTrait;

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
     * @var PushNormalizer
     */
    private $pushNormalizer;

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
     * @param PushNormalizer $pushNormalizer
     * @param JobQueueService $queue
     * @param ProblemRendererInterface $problemRenderer
     * @param Clock $clock
     */
    public function __construct(
        ResponseFormatter $formatter,
        BuildNormalizer $buildNormalizer,
        PushNormalizer $pushNormalizer,
        JobQueueService $queue,
        ProblemRendererInterface $problemRenderer,
        Clock $clock
    ) {
        $this->formatter = $formatter;
        $this->buildNormalizer = $buildNormalizer;
        $this->pushNormalizer = $pushNormalizer;

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
        $status = (count($jobs) > 0) ? 200 : 404;

        $data = [
            'count' => count($jobs)
        ];

        $embedded = [
            'jobs' => $this->formatQueue($jobs)
        ];

        $body = $this->formatter->buildResponse($request, $this->buildResource($data, $embedded));
        return $this->withHypermediaEndpoint($request, $response, $body, $status);
    }

    /**
     * @param Build[]|Push[] $queue
     *
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
}