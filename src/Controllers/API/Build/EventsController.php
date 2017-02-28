<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\API\Build;

use Hal\UI\API\Normalizer\BuildNormalizer;
use Hal\UI\API\Normalizer\EventNormalizer;
use Hal\UI\API\ResponseFormatter;
use Hal\UI\API\Utility\HypermediaResourceTrait;
use Hal\UI\Controllers\APITrait;
use Hal\UI\Service\EventLogService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Build;
use QL\Panthor\ControllerInterface;

class EventsController implements ControllerInterface
{
    use APITrait;
    use HypermediaResourceTrait;

    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @var EventLogService
     */
    private $logService;

    /**
     * @var EventNormalizer
     */
    private $eventNormalizer;

    /**
     * @var BuildNormalizer
     */
    private $buildNormalizer;

    /**
     * @param ResponseFormatter $formatter
     * @param EventLogService $logService
     * @param EventNormalizer $eventNormalizer
     * @param BuildNormalizer $buildNormalizer
     */
    public function __construct(
        ResponseFormatter $formatter,
        EventLogService $logService,
        EventNormalizer $eventNormalizer,
        BuildNormalizer $buildNormalizer
    ) {
        $this->formatter = $formatter;
        $this->logService = $logService;

        $this->eventNormalizer = $eventNormalizer;
        $this->buildNormalizer = $buildNormalizer;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $build = $request->getAttribute(Build::class);
        $events = $this->logService->getLogs($build);

        $data = [
            'count' => count($events)
        ];

        $embedded = [];

        $links = [
            'build' => $this->buildNormalizer->link($build),
        ];

        $shouldEmbed = $this->isEmbedded($request);

        if ($embeddedEvents = $this->buildEmbeddedEvents($events, $shouldEmbed)) {
            $embedded['events'] = $embeddedEvents;
        }

        if ($linkedEvents = $this->buildLinkedEvents($events, $shouldEmbed)) {
            $links['events'] = $linkedEvents;
        }

        $resource = $this->buildResource($data, $embedded, $links);
        $status = (count($events) > 0) ? 200 : 404;
        $data = $this->formatter->buildResponse($request, $resource);

        return $this->withHypermediaEndpoint($request, $response, $data, $status);
    }

    /**
     * @param array $events
     * @param bool $shouldEmbed
     *
     * @return array|null
     */
    private function buildEmbeddedEvents(array $events, $shouldEmbed): ?array
    {
        if (!$shouldEmbed) {
            return null;
        }

        return array_map(function($event) {
            return $this->eventNormalizer->resource($event);
        }, $events);
    }

    /**
     * @param array $events
     * @param bool $shouldEmbed
     *
     * @return array|null
     */
    private function buildLinkedEvents(array $events, $shouldEmbed): ?array
    {
        if ($shouldEmbed) {
            return null;
        }

        return array_map(function($event) {
            return $this->eventNormalizer->link($event);
        }, $events);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return bool
     */
    private function isEmbedded(ServerRequestInterface $request): bool
    {
        $embed = $request->getQueryParams()['embed'] ?? '';
        if (!$embed) {
            return false;
        }

        $embed = explode(',', $embed);

        return in_array('events', $embed);
    }
}
