<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Admin;

use QL\Hal\Services\DeploymentService;
use QL\Hal\Services\RepositoryService;
use QL\Hal\Services\ServerService;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

/**
 * @api
 */
class ManageDeployments
{
    /**
     * @param Twig_Template
     */
    private $tpl;

    /**
     * @param Service RepositoryService
     */
    private $repos;

    /**
     * @param Service ServerService
     */
    private $servers;

    /**
     * @param Service DeploymentService
     */
    private $deployments;

    /**
     * @param Twig_Template $tpl
     * @param RepositoryService $repos
     * @param ServerService $servers
     * @param DeploymentService $deployments
     */
    public function __construct(
        Twig_Template $tpl,
        RepositoryService $repos,
        ServerService $servers,
        DeploymentService $deployments
    )
    {
        $this->tpl = $tpl;
        $this->repos = $repos;
        $this->servers = $servers;
        $this->deployments = $deployments;
    }

    /**
     * @param Request $req
     * @param Response $res
     * @return null
     */
    public function __invoke(Request $req, Response $res)
    {
        $reposList = $this->repos->listAll();
        $serversList = $this->servers->listAll();
        $deploymentsList = $this->deployments->listAll();
        $res->body($this->tpl->render([
            'repositories' => $reposList,
            'servers' => $serversList,
            'deployments' => $deploymentsList
        ]));
    }
}
