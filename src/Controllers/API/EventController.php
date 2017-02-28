<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\API;

use Hal\UI\API\Normalizer\EventNormalizer;
use Hal\UI\API\ResponseFormatter;
use Hal\UI\Controllers\APITrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\EventLog;
use QL\Panthor\ControllerInterface;

class EventController implements ControllerInterface
{
    use APITrait;

    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @var EventNormalizer
     */
    private $normalizer;

    /**
     * @param ResponseFormatter $formatter
     * @param EventNormalizer $normalizer
     */
    public function __construct(ResponseFormatter $formatter, EventNormalizer $normalizer)
    {
        $this->formatter = $formatter;
        $this->normalizer = $normalizer;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $event = $request->getAttribute(EventLog::class);

        $data = $this->normalizer->resource($event, ['data']);

        $body = $this->formatter->buildResponse($request, $data);
        return $this->withHypermediaEndpoint($request, $response, $body, 200);
    }
}
