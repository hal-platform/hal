<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Admin;

use QL\Hal\Layout;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;
use QL\Hal\Services\RepositoryService;
use QL\Hal\Services\ArrangementService;
use QL\Hal\PushPermissionService;

/**
 * @api
 */
class ManagePermissions
{
    /**
     * @param Twig_Template
     */
    private $tpl;

    /**
     * @var \QL\Hal\PushPermissionService
     */
    private $pushPermissionService;

    /**
     * @var Layout
     */
    private $layout;

    /**
     * @var \QL\Hal\Services\RepositoryService
     */
    private $repos;

    /**
     * @param Twig_Template $tpl
     * @param RepositoryService $repos
     * @param PushPermissionService $pushPermissionService
     * @param Layout $layout
     */
    public function __construct(
        Twig_Template $tpl,
        RepositoryService $repos,
        PushPermissionService $pushPermissionService,
        Layout $layout
    ) {
        $this->tpl = $tpl;
        $this->repos = $repos;
        $this->pushPermissionService = $pushPermissionService;
        $this->layout = $layout;
    }

    /**
     * @param Request $req
     * @param Response $res
     * @return null
     */
    public function __invoke(Request $req, Response $res)
    {
        $repos = $this->repos->listAll();
        $permissions = array();

        foreach ($repos as $repo) {
            $permissions[$repo['ShortName']] = $user = $this->pushPermissionService->allUsersWithAccess($repo['ShortName']);
        }

        $context = [
            'permissions' => $permissions
        ];

        $res->body($this->layout->renderTemplateWithLayoutData($this->tpl, $context));
    }
}
