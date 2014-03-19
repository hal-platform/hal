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
    const LDAP_USER         = 'placeholder';
    const LDAP_PASSWORD     = 'placeholder';

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
     * @var \MCP\Corp\Account\LdapService
     */
    private $ldapService;

    /**
     * @param Twig_Template $tpl
     * @param UserService $userService
     * @param PushPermissionService $pushPermissionService
     * @param Layout $layout
     * @param LdapService $ldapService
     */
    public function __construct(
        Twig_Template $tpl,
        UserService $userService,
        PushPermissionService $pushPermissionService,
        Layout $layout,
        LdapService $ldapService
    ) {
        $this->tpl = $tpl;
        $this->userService = $userService;
        $this->pushPermissionService = $pushPermissionService;
        $this->layout = $layout;
        $this->ldapService = $ldapService;
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

        // this is dump, refactor in the future @todo
        $this->ldapService->authenticate(self::LDAP_USER, self::LDAP_PASSWORD, false);
        $ldapUser = $this->ldapService->getUserByCommonId($commonId);

        if (is_null($user)) {
            call_user_func($notFound);
            return;
        }

        $permissions = $this->pushPermissionService->repoEnvsCommonIdCanPushTo($commonId);
        $totalPushes = $this->userService->getTotalPushesByCommonId($commonId);

        $data = [
            'user' => $user,
            'ldapUser' => $ldapUser,
            'total_pushes' => $totalPushes,
            'permissions' => $permissions,
        ];

        $res->body($this->layout->renderTemplateWithLayoutData($this->tpl, $data));
    }
}
