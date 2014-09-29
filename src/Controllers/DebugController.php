<?php

namespace QL\Hal\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Debug Controller
 */
class DebugController
{
    /**
     *  Run the controller
     *
     *  @param Request $request
     *  @param Response $response
     *  @param array $params
     */
    public function __invoke(Request $request, Response $response, array $params = [])
    {
        // shoosh, nothing to see here
        phpinfo();
        die();
    }
}
