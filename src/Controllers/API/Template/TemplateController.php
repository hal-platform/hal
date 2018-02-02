<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\API\Template;

use Hal\Core\Entity\TargetTemplate;
use Hal\UI\API\Normalizer\TemplateNormalizer;
use Hal\UI\API\ResponseFormatter;
use Hal\UI\Controllers\APITrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;

class TemplateController implements ControllerInterface
{
    use APITrait;

    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @var TemplateNormalizer
     */
    private $normalizer;

    /**
     * @param ResponseFormatter $formatter
     * @param TemplateNormalizer $normalizer
     */
    public function __construct(ResponseFormatter $formatter, TemplateNormalizer $normalizer)
    {
        $this->formatter = $formatter;
        $this->normalizer = $normalizer;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $template = $request->getAttribute(TargetTemplate::class);

        $resource = $this->normalizer->resource($template, ['environment']);
        $body = $this->formatter->buildHypermediaResponse($request, $resource);

        return $this->withHypermediaEndpoint($request, $response, $body);
    }
}
