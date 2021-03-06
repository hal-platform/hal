<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\API\Application;

use Hal\Core\Entity\Application;
use Hal\UI\API\Normalizer\ApplicationNormalizer;
use Hal\UI\API\ResponseFormatter;
use Hal\UI\Controllers\APITrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;

class ApplicationController implements ControllerInterface
{
    use APITrait;

    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @var ApplicationNormalizer
     */
    private $normalizer;

    /**
     * @param ResponseFormatter $formatter
     * @param ApplicationNormalizer $normalizer
     */
    public function __construct(ResponseFormatter $formatter, ApplicationNormalizer $normalizer)
    {
        $this->formatter = $formatter;
        $this->normalizer = $normalizer;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $application = $request->getAttribute(Application::class);

        $resource = $this->normalizer->resource($application);
        $body = $this->formatter->buildHypermediaResponse($request, $resource);

        return $this->withHypermediaEndpoint($request, $response, $body, 200);
    }
}
