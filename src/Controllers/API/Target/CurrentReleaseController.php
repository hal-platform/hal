<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\API\Target;

use Hal\Core\Entity\Target;
use Hal\UI\API\Normalizer\ReleaseNormalizer;
use Hal\UI\API\ResponseFormatter;
use Hal\UI\Controllers\APITrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\HTTPProblem\ProblemRendererInterface;

class CurrentReleaseController implements ControllerInterface
{
    use APITrait;

    private const ERR_NEVER_DEPLOYED = 'No release found. Target has never been deployed to.';

    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @var ProblemRendererInterface
     */
    private $problem;

    /**
     * @var ReleaseNormalizer
     */
    private $normalizer;

    /**
     * @param ResponseFormatter $formatter
     * @param ProblemRendererInterface $problem
     * @param ReleaseNormalizer $normalizer
     */
    public function __construct(
        ResponseFormatter $formatter,
        ReleaseNormalizer $normalizer,
        ProblemRendererInterface $problem
    ) {
        $this->formatter = $formatter;
        $this->normalizer = $normalizer;
        $this->problem = $problem;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $target = $request->getAttribute(Target::class);
        $push = $target->lastJob();

        if (!$push) {
            return $this->withProblem($this->problem, $response, 404, self::ERR_NEVER_DEPLOYED);
        }

        $resource = $this->normalizer->resource($push);
        $body = $this->formatter->buildHypermediaResponse($request, $resource);

        return $this->withHypermediaEndpoint($request, $response, $body, 200);
    }
}
