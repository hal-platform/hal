<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\API\Build;

use Hal\Core\Entity\Build;
use Hal\UI\API\HypermediaResource;
use Hal\UI\API\ResponseFormatter;
use Hal\UI\Controllers\APITrait;
use Hal\UI\Service\EventLogService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;

class EventsController implements ControllerInterface
{
    use APITrait;

    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @var EventLogService
     */
    private $logService;

    /**
     * @param ResponseFormatter $formatter
     * @param EventLogService $logService
     */
    public function __construct(ResponseFormatter $formatter, EventLogService $logService)
    {
        $this->formatter = $formatter;
        $this->logService = $logService;
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

        $resource = new HypermediaResource($data, [], [
            'build' => $build,
            'events' => $events
        ]);

        if ($this->isEmbedded($request)) {
            $resource->withEmbedded(['events']);
        }

        $status = (count($events) > 0) ? 200 : 404;
        $data = $this->formatter->buildHypermediaResponse($request, $resource);

        return $this->withHypermediaEndpoint($request, $response, $data, $status);
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
