<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\API\Target;

use Hal\UI\API\ResponseFormatter;
use Hal\UI\API\Normalizer\DeploymentNormalizer;
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
     * @var DeploymentNormalizer
     */
    private $normalizer;

    /**
     * @param ResponseFormatter $formatter
     * @param DeploymentNormalizer $normalizer
     */
    public function __construct(ResponseFormatter $formatter, DeploymentNormalizer $normalizer)
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

        $data = $this->normalizer->resource($target, ['server']);

        $body = $this->formatter->buildResponse($request, $data);
        return $this->withHypermediaEndpoint($request, $response, $body);
    }
}
