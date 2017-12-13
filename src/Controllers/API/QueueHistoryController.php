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

class QueueHistoryController implements ControllerInterface
{
    use APITrait;

    private const ERR_MALFORMED_DATE = 'Malformed Datetime! Dates must be ISO8601 UTC.';

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
     * @var Clock
     */
    private $clock;

    /**
     * @var string
     */
    private $timezone;

    /**
     * @param ResponseFormatter $formatter
     * @param JobQueueService $queue
     * @param Clock $clock
     * @param string $timezone
     */
    public function __construct(
        ResponseFormatter $formatter,
        BuildNormalizer $buildNormalizer,
        ReleaseNormalizer $releaseNormalizer,
        JobQueueService $queue,
        Clock $clock,
        $timezone
    ) {
        $this->formatter = $formatter;
        $this->buildNormalizer = $buildNormalizer;
        $this->releaseNormalizer = $releaseNormalizer;

        $this->queue = $queue;
        $this->clock = $clock;
        $this->timezone = $timezone;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $date = $request
            ->getAttribute('route')
            ->getArgument('date');

        [$from, $to] = $this->getTimeRange($date);

        $now = $this->clock->read();
        $isToday = $now->format('Y-m-d', 'UTC') === $from->format('Y-m-d', 'UTC');

        $jobs = $this->queue->getHistory($from, $to);

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
     * @param string|null $date
     *
     * @return array
     */
    private function getTimeRange($date = '')
    {
        if ($date) {
            $date = $this->clock->fromString($date, 'Y-m-d');
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
}
