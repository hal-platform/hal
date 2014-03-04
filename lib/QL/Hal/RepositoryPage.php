<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal;

use MCP\Corp\Account\User;
use Slim\Http\Request;
use Slim\Http\Response;
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
     * @var PushPermissionService
     */
    private $pushPermissionsService;

    /**
     * @var User
     */
    private $currentUser;

    /**
     * @var Layout
     */
    private $layout;

    /**
     * @param Twig_Template $tpl
     * @param RepositoryService $repoService
     * @param DeploymentService $deploymentService
     * @param ServerService $serverService
     * @param LogService $logService
     * @param PushPermissionService $pushPermissionsService
     * @param User $currentUser
     * @param Layout $layout
     */
    public function __construct(
        Twig_Template $tpl,
        RepositoryService $repoService,
        DeploymentService $deploymentService,
        ServerService $serverService,
        LogService $logService,
        PushPermissionService $pushPermissionsService,
        User $currentUser,
        Layout $layout
    ) {
        $this->tpl = $tpl;
        $this->repoService = $repoService;
        $this->deploymentService = $deploymentService;
        $this->serverService = $serverService;
        $this->logService = $logService;
        $this->pushPermissionsService = $pushPermissionsService;
        $this->currentUser = $currentUser;
        $this->layout = $layout;
    }

    /**
     * @param Request $req
     * @param Response $res
     * @param array $params
     * @param callable $notFound
     * @return null
     */
    public function __invoke(Request $req, Response $res, array $params = null, callable $notFound = null)
    {
        $shortName = $params['name'];
        $repo = $this->repoService->getFromName($shortName);
        $renderData = [];

        if (!$repo) {
            call_user_func($notFound);
            return;
        }

        if ($req->get('page')) {
            $pageNumber = $req->get('page');
            $renderData['currentPage'] = $pageNumber;
            $pages = $this->logService->paginate($shortName, $pageNumber);
        } else {
            $pages = $this->logService->paginate($shortName);
        }

        $deployments = $this->deploymentService->listAllByRepoId($repo['RepositoryId']);

        $renderData['repo'] = $repo;
        $renderData['deployments'] = $deployments;
        $renderData['logs'] = $pages[0];
        $renderData['totalLogEntries'] = $this->logService->getCount($shortName);
        $renderData['totalLogPages'] = $pages[1];
        $renderData['user'] = $this->currentUser;

        $res->body($this->layout->renderTemplateWithLayoutData($this->tpl, $renderData));
    }
}
