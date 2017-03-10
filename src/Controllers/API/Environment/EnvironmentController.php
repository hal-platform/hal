<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\API\Environment;

use Hal\UI\API\ResponseFormatter;
use Hal\UI\Controllers\APITrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Environment;
use QL\Panthor\ControllerInterface;

class EnvironmentController implements ControllerInterface
{
     use APITrait;

    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @param ResponseFormatter $formatter
     */
    public function __construct(ResponseFormatter $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $environment = $request->getAttribute(Environment::class);

        $body = $this->formatter->buildResponse($request, $environment);
        return $this->withHypermediaEndpoint($request, $response, $body);
    }
}
