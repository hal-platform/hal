<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api;

use QL\Hal\Helpers\ApiHelper;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * API Index Controller
 */
class IndexController
{
    /**
     * @type ApiHelper
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
     */
    public function __invoke(Request $request, Response $response)
    {
        $links = [
            'self' => ['href' => 'api.index'],
            'environments' => ['href' => 'api.environments'],
            'servers' => ['href' => 'api.servers'],
            'groups' => ['href' => 'api.groups'],
            'users' => ['href' => 'api.users'],
            'repositories' => ['href' => 'api.repositories']
        ];

        $content = [
            '_links' => $this->api->parseLinks($links)
        ];


        $this->api->prepareResponse($response, $content);
    }
}
