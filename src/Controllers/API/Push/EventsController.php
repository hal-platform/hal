<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\API\Push;

use Hal\UI\API\Normalizer\EventNormalizer;
use Hal\UI\API\Normalizer\PushNormalizer;
use Hal\UI\API\ResponseFormatter;
use Hal\UI\API\Utility\HypermediaResourceTrait;
use Hal\UI\Controllers\APITrait;
use Hal\UI\Service\EventLogService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Push;
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
     * @var PushNormalizer
     */
    private $pushNormalizer;

    /**
     * @param ResponseFormatter $formatter
     * @param EventNormalizer $eventNormalizer
     * @param PushNormalizer $pushNormalizer
     * @param EventLogService $logService
     */
    public function __construct(
        ResponseFormatter $formatter,
        EventLogService $logService,
        EventNormalizer $eventNormalizer,
        PushNormalizer $pushNormalizer
    ) {
        $this->formatter = $formatter;
        $this->logService = $logService;

        $this->eventNormalizer = $eventNormalizer;
        $this->pushNormalizer = $pushNormalizer;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $push = $request->getAttribute(Push::class);
        $events = $this->logService->getLogs($push);

        $data = [
            'count' => count($events)
        ];

        $embedded = [];

        $links = [
            'push' => $this->pushNormalizer->link($push),
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
