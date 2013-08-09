<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\GitBert2;

use QL\GitBert2\Services\ServerService;
use Slim\Http\Response;
use Twig_Template;

class GBServers 
{
    /**
     * @param Response
     */
    private $response;

    /**
     * @param Twig_Template
     */
    private $tpl;

    /**
     * @var ServerService
     */
    private $servers;

    /**
     * @param Response $response
     * @param Twig_Template $tpl
     * @param ServerService $servers
     */
    public function __construct(Response $response, Twig_Template $tpl, ServerService $servers)
    {
        $this->response = $response;
        $this->tpl = $tpl;
        $this->servers = $servers;
    }

    /**
     * @return null
     */
    public function __invoke()
    {
        $serversList = $this->servers->listAll();
        $this->response->body($this->tpl->render(['servers' => $serversList]));
    }
}
