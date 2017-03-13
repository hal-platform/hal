<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\API\Target;

use Hal\UI\API\ResponseFormatter;
use Hal\UI\API\Normalizer\TargetNormalizer;
use Hal\UI\Controllers\APITrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Deployment;
use QL\Panthor\ControllerInterface;

class TargetController implements ControllerInterface
{
    use APITrait;

    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @var TargetNormalizer
     */
    private $normalizer;

    /**
     * @param ResponseFormatter $formatter
     * @param TargetNormalizer $normalizer
     */
    public function __construct(ResponseFormatter $formatter, TargetNormalizer $normalizer)
    {
        $this->formatter = $formatter;
        $this->normalizer = $normalizer;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $target = $request->getAttribute(Deployment::class);

        $resource = $this->normalizer->resource($target, ['server']);
        $body = $this->formatter->buildHypermediaResponse($request, $resource);

        return $this->withHypermediaEndpoint($request, $response, $body);
    }
}
