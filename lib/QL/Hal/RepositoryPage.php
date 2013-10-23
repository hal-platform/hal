<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal;

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Slim;
use Twig_Template;
use QL\Hal\Services\RepositoryService;
use QL\Hal\Services\DeploymentService;
use QL\Hal\Services\ServerService;
use QL\Hal\Services\LogService;

/**
 * @api
 */
class RepositoryPage
{
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
     * @var LogService
     */
    private $logService;


    /**
     * @param Twig_Template $tpl
     * @param RepositoryService $repoService
     * @param DeploymentService $deploymentService
     * @param ServerService $serverService
     * @param LogService $logService
     */
    public function __construct(Twig_Template $tpl, RepositoryService $repoService, DeploymentService $deploymentService, ServerService $serverService, LogService $logService)
    {
        $this->tpl = $tpl;
        $this->repoService = $repoService;
        $this->deploymentService = $deploymentService;
        $this->serverService = $serverService;
        $this->logService = $logService;
    }

    /**
     * @param Request $req
     * @param Response $res
     * @param array $params
     * @param callable $notFound
     * @internal param string $shortName
     * @return null
     */
    public function __invoke(Request $req, Response $res, array $params = null, callable $notFound = null)
    {
        $shortName = $params['name'];
        $repo = $this->repoService->getFromName($shortName);

        if (!$repo) {
            call_user_func($notFound);
            return;
        }

        $deployments = $this->deploymentService->listAllByRepoId($repo['RepositoryId']);
        $logEntries = $this->logService->getByRepo($shortName);
        $totalLogEntries = count($logEntries);
        $res->body($this->tpl->render([
            'deployments' => $deployments,
            'repo' => $repo,
            'logs' => $logEntries,
            'totalLogs' => $totalLogEntries
        ]));
    }
}
