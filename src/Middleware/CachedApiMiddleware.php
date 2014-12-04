<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Middleware;

use QL\Hal\Api\ResponseFormatter;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Exception\Stop;

/**
 *  Check if the response is cached and if so, halt processing so the controller is not hit.
 */
class CachedApiMiddleware
{
    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @param ResponseFormatter $formatter
     */
    public function __construct(
        ResponseFormatter $formatter
    ) {
        $this->formatter = $formatter;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @throws Stop
     */
    public function __invoke(Request $request, Response $response)
    {
        if ($this->formatter->sendCachedResponse($response)) {
            throw new Stop;
        }
    }
}
