<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Middleware;

use QL\Hal\Api\ResponseFormatter;
use QL\Panthor\MiddlewareInterface;
use Slim\Http\Response;
use Slim\Exception\Stop;

/**
 *  Check if the response is cached and if so, halt processing so the controller is not hit.
 */
class CachedApiMiddleware implements MiddlewareInterface
{
    /**
     * @type ResponseFormatter
     */
    private $formatter;

    /**
     * @type Response
     */
    private $response;

    /**
     * @param ResponseFormatter $formatter
     */
    public function __construct(ResponseFormatter $formatter, Response $response)
    {
        $this->formatter = $formatter;
        $this->response = $response;
    }

    /**
     * {@inheritdoc}
     * @throws Stop
     */
    public function __invoke()
    {
        if ($this->formatter->sendCachedResponse($this->response)) {
            throw new Stop;
        }
    }
}
