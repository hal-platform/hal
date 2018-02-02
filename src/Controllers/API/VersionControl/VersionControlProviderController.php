<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\API\VersionControl;

use Hal\Core\Entity\System\VersionControlProvider;
use Hal\UI\API\Normalizer\VersionControlProviderNormalizer;
use Hal\UI\API\ResponseFormatter;
use Hal\UI\Controllers\APITrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;

class VersionControlProviderController implements ControllerInterface
{
    use APITrait;

    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @var VersionControlProviderNormalizer
     */
    private $normalizer;

    /**
     * @param ResponseFormatter $formatter
     * @param VersionControlProviderNormalizer $normalizer
     */
    public function __construct(ResponseFormatter $formatter, VersionControlProviderNormalizer $normalizer)
    {
        $this->formatter = $formatter;
        $this->normalizer = $normalizer;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $vcs = $request->getAttribute(VersionControlProvider::class);

        $resource = $this->normalizer->resource($vcs);
        $body = $this->formatter->buildHypermediaResponse($request, $resource);

        return $this->withHypermediaEndpoint($request, $response, $body, 200);
    }
}
