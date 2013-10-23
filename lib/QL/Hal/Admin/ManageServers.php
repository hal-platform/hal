<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Admin;

use QL\Hal\Services\EnvironmentService;
use QL\Hal\Services\ServerService;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

/**
 * @api
 */
class ManageServers
{
    /**
     * @param Twig_Template
     */
    private $tpl;

    /**
     * @var ServerService
     */
    private $serverService;

    /**
     * @var EnvironmentService
     */
    private $envService;

    /**
     * @param Twig_Template $tpl
     * @param ServerService $serverService
     * @param EnvironmentService $envService
     */
    public function __construct(
        Twig_Template $tpl,
        ServerService $serverService,
        EnvironmentService $envService
    ) {
        $this->tpl = $tpl;
        $this->serverService = $serverService;
        $this->envService = $envService;
    }

    /**
     * @param Request $req
     * @param Response $res
     * @return null
     */
    public function __invoke(Request $req, Response $res)
    {
        $serversList = $this->serverService->listAll();
        $envList = $this->envService->listAll();
        $res->body($this->tpl->render([
            'servers' => $serversList,
            'envs' => $envList,
        ]));
    }
}
