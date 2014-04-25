<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Admin;

use QL\Hal\Layout;
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
     * @var Twig_Template
     */
    private $tpl;

    /**
     * @var RepositoryService
     */
    private $repos;

    /**
     * @var ServerService
     */
    private $servers;

    /**
     * @var DeploymentService
     */
    private $deployments;

    /**
     * @var Layout
     */
    private $layout;

    /**
     * @param Twig_Template $tpl
     * @param RepositoryService $repos
     * @param ServerService $servers
     * @param DeploymentService $deployments
     * @param Layout $layout
     */
    public function __construct(
        Twig_Template $tpl,
        RepositoryService $repos,
        ServerService $servers,
        DeploymentService $deployments,
        Layout $layout
    )
    {
        $this->tpl = $tpl;
        $this->repos = $repos;
        $this->servers = $servers;
        $this->deployments = $deployments;
        $this->layout = $layout;
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

        $data = [
            'repositories' => $reposList,
            'servers' => $serversList,
            'deployments' => $deploymentsList
        ];

        $res->body($this->layout->renderTemplateWithLayoutData($this->tpl, $data));
    }
}
