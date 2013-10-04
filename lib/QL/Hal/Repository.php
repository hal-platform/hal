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
     * @param string $shortName
     * @param callable $notFound
     * @return null
     */
    public function __invoke($shortName, callable $notFound)
    {
        $repo = $this->repoService->getFromName($shortName);

        if (!$repo) {
            call_user_func($notFound);
            return;
        }
        
        $deployments = $this->deploymentService->listAllByRepoId($repo['RepositoryId']);
        $this->response->body($this->tpl->render([
            'deployments' => $deployments,
            'repo' => $repo,
        ]));
    }
}
