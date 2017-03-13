<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\API\Server;

use Hal\UI\API\Normalizer\ServerNormalizer;
use Hal\UI\API\ResponseFormatter;
use Hal\UI\Controllers\APITrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Server;
use QL\Panthor\ControllerInterface;

class ServerController implements ControllerInterface
{
    use APITrait;

    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @var ServerNormalizer
     */
    private $normalizer;

    /**
     * @param ResponseFormatter $formatter
     * @param ServerNormalizer $normalizer
     */
    public function __construct(ResponseFormatter $formatter, ServerNormalizer $normalizer)
    {
        $this->formatter = $formatter;
        $this->normalizer = $normalizer;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $server = $request->getAttribute(Server::class);

        $resource = $this->normalizer->resource($server, ['environment']);
        $body = $this->formatter->buildHypermediaResponse($request, $resource);

        return $this->withHypermediaEndpoint($request, $response, $body);
    }
}
