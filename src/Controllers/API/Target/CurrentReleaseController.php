<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\API\Target;

use Hal\UI\Controllers\APITrait;
use Hal\UI\API\ResponseFormatter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Deployment;
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
     * @param ResponseFormatter $formatter
     * @param ProblemRendererInterface $problem
     */
    public function __construct(ResponseFormatter $formatter, ProblemRendererInterface $problem)
    {
        $this->formatter = $formatter;
        $this->problem = $problem;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $target = $request->getAttribute(Deployment::class);
        $push = $target->push();

        if (!$push) {
            return $this->withProblem($this->problem, $response, 404, self::ERR_NEVER_DEPLOYED);
        }

        $data = $this->formatter->buildResponse($request, $push);
        return $this->withHypermediaEndpoint($request, $response, $data, 200);
    }
}
