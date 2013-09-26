<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal;

use Slim\Http\Response;
use Slim\Slim;
use Twig_Template;
use QL\Hal\Services\RepositoryService;
use QL\Hal\Services\DeploymentService;
use QL\Hal\Services\ServerService;

/**
 * @api
 */
class Repository
{
    /**
     * @var Response
     */
    private $response;

    /**
     * @var Twig_Template
     */
    private $tpl;

    /**
     * @var RepositoryService
     */
    private $repoService;

    /**
     * @var DeploymentService
     */
    private $deploymentService;

    /**
     * @var ServerService
     */
    private $serverService;


    /**
     * @param Response $response
     * @param Twig_Template $tpl
     * @param RepositoryService $repoService
     * @param DeploymentService $deploymentService
     * @param ServerService $serverService
     */
    public function __construct(Response $response, Twig_Template $tpl, RepositoryService $repoService, DeploymentService $deploymentService, ServerService $serverService)
    {
        $this->response = $response;
        $this->tpl = $tpl;
        $this->repoService = $repoService;
        $this->deploymentService = $deploymentService;
        $this->serverService = $serverService;
    }

    /**
     * @param int $commonId
     * @param Slim $app
     * @return null
     */
    public function __invoke($shortName, Slim $app)
    {
       /* $repoId = $this->getRepoId($shortName);
        if (is_null($repoId)) {
            $app->notFound();
            return;
        }
    
        if ($repoId) {
            $id = $repoId['RepositoryId'];
            $deployments = $this->getDeployments($id);
        } */
        
        $deployments = $this->getDeployments($shortName);
        $servers = $this->getServers();
        $this->response->body($this->tpl->render([
            'deployments' => $deployments,
         #   'servers' => $servers,
         #   'servers' => $servers,
         #   'env' => $envs,
            'repo' => $shortName
        ]));
    }

    private function getRepoId($shortName)
    {
        $repoId = $this->repoService->getFromName($shortName);
        return $repoId;
    }

    private function getDeployments($shortName)
    {
        $deployments = $this->deploymentService->listForRepository($shortName);
        return $deployments;
    }

    private function getServers()
    {
        $serverList = $this->serverService->listAll();
        return $serverList;
    }
}
