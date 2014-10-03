<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Middleware;

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Exception\Stop;
use QL\Hal\Helpers\ApiHelper;

/**
 *  Check if the response is cached and if so, halt processing so the controller is not hit.
 */
class CachedApiMiddleware
{
    /**
     * @var ApiHelper
     */
    private $api;

    /**
     * @param ApiHelper $api
     */
    public function __construct(ApiHelper $api)
    {
        $this->api = $api;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @throws Stop
     */
    public function __invoke(Request $request, Response $response)
    {
        if ($this->api->checkForCachedResponse($response)) {
            throw new Stop;
        }
    }
}
