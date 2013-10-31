<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal;

use MCP\Corp\Account\LdapService;
use QL\Hal\Services\UserService;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

/**
 * @api
 */
class Users
{
    /**
     * @var Twig_Template
     */
    private $tpl;
    /**
     * @var UserService
     */
    private $userService;
    /**
     * @var PushPermissionService
     */
    private $pushPermissionService;

    /**
     * @var Layout
     */
    private $layout;

    /**
     * @param Twig_Template $tpl
     * @param UserService $userService
     * @param PushPermissionService $pushPermissionService
     * @param Layout $layout
     */
    public function __construct(
        Twig_Template $tpl,
        UserService $userService,
        PushPermissionService $pushPermissionService,
        Layout $layout
    ) {
        $this->tpl = $tpl;
        $this->userService = $userService;
        $this->pushPermissionService = $pushPermissionService;
        $this->layout = $layout;
    }

    /**
     * @param Request $req
     * @param \Slim\Http\Response $res
     * @param array $params
     * @param callable $notFound
     * @return null
     */
    public function __invoke(Request $req, Response $res, array $params = null, callable $notFound = null)
    {
        $commonId = $params['id'];
        $user = $this->userService->getById($commonId);
        if (is_null($user)) {
            call_user_func($notFound);
            return;
        }

        $permissions = $this->pushPermissionService->repoEnvsCommonIdCanPushTo($commonId);
        $totalPushes = $this->userService->getTotalPushesByCommonId($commonId);

        $data = [
            'user' => $user,
            'total_pushes' => $totalPushes,
            'permissions' => $permissions,
        ];

        $res->body($this->layout->renderTemplateWithLayoutData($this->tpl, $data));
    }
}
