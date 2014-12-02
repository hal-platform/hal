<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api;

use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Api\Utility\HypermediaLinkTrait;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * API Index Controller
 */
class IndexController
{
    use HypermediaLinkTrait;

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
     */
    public function __invoke(Request $request, Response $response)
    {
        $this->formatter->respond([
            '_links' => [
                'environments' => ['href' => 'api.environments'],
                'servers' => ['href' => 'api.servers'],
                'groups' => ['href' => 'api.groups'],
                'users' => ['href' => 'api.users'],
                'repositories' => ['href' => 'api.repositories'],
                'queue' => ['href' => 'api.queue']
            ]
        ]);
    }
}
