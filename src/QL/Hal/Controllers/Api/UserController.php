<?php

namespace QL\Hal\Controllers\Api;

use Slim\Http\Request;
use Slim\Http\Response;
use QL\Hal\Helpers\ApiHelper;

class UserController
{
    /**
     * @var ApiHelper
     */
    private $api;

    /**
     * @param ApiHelper $api
     */
    public function __construct(
        ApiHelper $api
    ) {
        $this->api = $api;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        $content = [];

        if (false) {
            call_user_func($notFound);
            return;
        }

        $this->api->prepareResponse($response, [], $content);
    }
}
