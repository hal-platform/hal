<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers;

use Hal\UI\API\ResponseFormatter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\HTTPProblem\HTTPProblem;
use QL\Panthor\HTTPProblem\ProblemRendererInterface;
use QL\Panthor\HTTPProblem\ProblemRenderingTrait;

trait APITrait
{
    use ProblemRenderingTrait;

    /**
     * Add the rendered endpoint to the response.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string $data
     * @param int $status
     *
     * @return ResponseInterface
     */
    private function withHypermediaEndpoint(
        ServerRequestInterface $request,
        ResponseInterface $response,
        string $data,
        int $status = 200
    ): ResponseInterface {

        $response = $response
            ->withStatus($status)
            ->withHeader('Content-Type', ResponseFormatter::API_CONTENT_TYPE);

        // Ugh IE sucks
        // http://www.dashbay.com/2011/05/internet-explorer-caches-ajax/
        if ($request->hasHeader('X-Requested-With') && $request->getHeader('X-Requested-With')[0] === 'XMLHttpRequest') {
            $response = $response->withHeader('Cache-Control', 'no-cache');
        }

        return $this->withNewBody($response, $data);
    }

    /**
     * Render HTTP Problem to the response.
     *
     * @param ProblemRendererInterface $renderer
     * @param ResponseInterface $response
     * @param string $message
     * @param int $status
     *
     * @return ResponseInterface
     */
    private function withProblem(
        ProblemRendererInterface $renderer,
        ResponseInterface $response,
        int $status,
        string $message
    ): ResponseInterface {
        $problem = new HTTPProblem($status, $message);

        return $this->renderProblem($response, $renderer, $problem);
    }
}
