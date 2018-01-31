<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\API\Release;

use Hal\UI\API\HypermediaResource;
use Hal\UI\API\ResponseFormatter;
use Hal\UI\Controllers\APITrait;
use Hal\UI\Service\JobEventsService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Hal\Core\Entity\JobType\Release;
use QL\Panthor\ControllerInterface;

class EventsController implements ControllerInterface
{
    use APITrait;

    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @var JobEventsService
     */
    private $logService;

    /**
     * @param ResponseFormatter $formatter
     * @param JobEventsService $logService
     */
    public function __construct(ResponseFormatter $formatter, JobEventsService $logService)
    {
        $this->formatter = $formatter;
        $this->logService = $logService;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $release = $request->getAttribute(Release::class);
        $events = $this->logService->getEvents($release);

        $data = [
            'count' => count($events)
        ];

        $resource = new HypermediaResource($data, [], [
            'push' => $release,
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
