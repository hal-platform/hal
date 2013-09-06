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
use Slim\Http\Response;
use Twig_Template;

/**
 * @api
 */
class ManageDeployments
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
     * @param Response $response
     * @param Twig_Template $tpl
     * @param RepositoryService $repos
     * @param ServerService $servers
     * @param DeploymentService $deployments
     */
    public function __construct(
        Response $response, 
        Twig_Template $tpl,
        RepositoryService $repos,
        ServerService $servers,
        DeploymentService $deployments
    )
    {
        $this->response = $response;
        $this->tpl = $tpl;
        $this->repos = $repos;
        $this->servers = $servers;
        $this->deployments = $deployments;
    }

    /**
     * @return null
     */
    public function __invoke()
    {
        $reposList = $this->repos->listAll();
        $serversList = $this->servers->listAll();
        $deploymentsList = $this->deployments->listAll();
        $this->response->body($this->tpl->render([
                        'repositories' => $reposList, 
                        'servers' => $serversList, 
                        'deployments' => $deploymentsList
        ]));
    }
}
