<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\API\Release;

use Hal\UI\API\Normalizer\ReleaseNormalizer;
use Hal\UI\API\ResponseFormatter;
use Hal\UI\Controllers\APITrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Hal\Core\Entity\JobType\Release;
use QL\Panthor\ControllerInterface;

class ReleaseController implements ControllerInterface
{
    use APITrait;

    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @var ReleaseNormalizer
     */
    private $normalizer;

    /**
     * @param ResponseFormatter $formatter
     * @param ReleaseNormalizer $normalizer
     */
    public function __construct(ResponseFormatter $formatter, ReleaseNormalizer $normalizer)
    {
        $this->formatter = $formatter;
        $this->normalizer = $normalizer;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $push = $request->getAttribute(Release::class);

        $resource = $this->normalizer->resource($push);
        $body = $this->formatter->buildHypermediaResponse($request, $resource);

        return $this->withHypermediaEndpoint($request, $response, $body, 200);
    }
}
