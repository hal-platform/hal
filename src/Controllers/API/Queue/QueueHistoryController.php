<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\API\Queue;

use Hal\Core\Entity\JobType\Build;
use Hal\Core\Entity\JobType\Release;
use Hal\UI\API\HypermediaResource;
use Hal\UI\API\Normalizer\BuildNormalizer;
use Hal\UI\API\Normalizer\ReleaseNormalizer;
use Hal\UI\API\ResponseFormatter;
use Hal\UI\Controllers\APITrait;
use Hal\UI\Service\JobQueueService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;

class QueueHistoryController implements ControllerInterface
{
    use APITrait;

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
     * @var string
     */
    private $timezone;

    /**
     * @param ResponseFormatter $formatter
     * @param JobQueueService $queue
     * @param string $timezone
     */
    public function __construct(
        ResponseFormatter $formatter,
        BuildNormalizer $buildNormalizer,
        ReleaseNormalizer $releaseNormalizer,
        JobQueueService $queue,
        $timezone
    ) {
        $this->formatter = $formatter;
        $this->buildNormalizer = $buildNormalizer;
        $this->releaseNormalizer = $releaseNormalizer;

        $this->queue = $queue;
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

        [$from, $to] = $this->queue->getTimeRange($date, $this->timezone);

        $jobs = $this->queue->getHistory($from, $to);

        $data = [
            'count' => count($jobs),
        ];

        $links = [];

        $resource = new HypermediaResource($data, $links, [
            'jobs' => $this->formatQueue($jobs),
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
}
