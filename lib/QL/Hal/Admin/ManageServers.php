<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Admin;

use QL\Hal\Services\EnvironmentService;
use QL\Hal\Services\ServerService;
use Slim\Http\Response;
use Twig_Template;

/**
 * @api
 */
class ManageServers
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
    private $serverService;

    /**
     * @var EnvironmentService
     */
    private $envService;

    /**
     * @param Response $response
     * @param Twig_Template $tpl
     * @param ServerService $serverService
     * @param EnvironmentService $envService
     */
    public function __construct(
        Response $response,
        Twig_Template $tpl,
        ServerService $serverService,
        EnvironmentService $envService
    ) {
        $this->response = $response;
        $this->tpl = $tpl;
        $this->serverService = $serverService;
        $this->envService = $envService;
    }

    /**
     * @return null
     */
    public function __invoke()
    {
        $serversList = $this->serverService->listAll();
        $envList = $this->envService->listAll();
        $this->response->body($this->tpl->render([
            'servers' => $serversList,
            'envs' => $envList,
        ]));
    }
}
