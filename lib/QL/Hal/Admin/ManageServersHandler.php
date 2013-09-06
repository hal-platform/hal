<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Admin;

use QL\Hal\Services\ServerService;
use Slim\Http\Response;
use Slim\Http\Request;
use Twig_Template;

/**
 * @api
 */
class ManageServersHandler
{
    /**
     * @var Response
     */
    private $response;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Twig_Template
     */
    private $tpl;

    /**
     * @var ServerService
     */
    private $servers;

    /**
     * @param Response $response
     * @param Request $request
     * @param Twig_Template $tpl
     * @param ServerService $servers
     */
    public function __construct(
        Response $response,
        Request $request,
        Twig_Template $tpl,
        ServerService $servers
    ) {
        $this->response = $response;
        $this->request = $request;
        $this->tpl = $tpl;
        $this->servers = $servers;
    }

    public function __invoke()
    {
        $hostname = $this->request->post('hostname');
        $envId = $this->request->post('envId');

        if (!$hostname) {
            $this->response->body($this->tpl->render(['error' => "all fields are required"]));
            return;
        }

        $this->servers->create($hostname, $envId);
        $this->response->status(302);
        $this->response['Location'] = 'http://' . $this->request->getHost() . '/servers';
    }
}
