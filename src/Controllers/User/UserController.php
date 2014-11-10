<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\User;

use MCP\Corp\Account\LdapService;
use MCP\Corp\Account\User as LdapUser;
use QL\Hal\Core\Entity\Repository\UserRepository;
use QL\Hal\Core\Entity\User;
use QL\Hal\Services\PermissionsService;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

class UserController
{
    /**
     *  @var Twig_Template
     */
    private $template;

    /**
     * @var PermissionsService
     */
    private $permissions;

    /**
     *  @var LdapService
     */
    private $ldap;

    /**
     *  @var UserRepository
     */
    private $userRepo;

    /**
     *  @var LdapUser
     */
    private $ldapUser;

    /**
     * @param Twig_Template $template
     * @param LdapService $ldap
     * @param UserRepository $userRepo
     * @param LdapUser $ldapUser
     * @param PermissionsService $permissions
     */
    public function __construct(
        Twig_Template $template,
        LdapService $ldap,
        UserRepository $userRepo,
        LdapUser $ldapUser,
        PermissionsService $permissions
    ) {
        $this->template = $template;
        $this->permissions = $permissions;
        $this->ldap = $ldap;
        $this->userRepo = $userRepo;
        $this->ldapUser = $ldapUser;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @param callable $notFound
     *
     * @return null
     */
    public function __invoke(Request $request, Response $response, array $params = null, callable $notFound = null)
    {
        // default to current user
        $id = (isset($params['id'])) ? $params['id'] : $this->ldapUser->commonId();

        if (!$user = $this->userRepo->findOneBy(['id' => $id])) {
            return call_user_func($notFound);
        }

        $rendered = $this->template->render([
            'user' => $user,
            'ldapUser' => $this->ldap->getUserByCommonId($id),
            'permissions' => $this->permissions->userPushPermissionPairs($user->getHandle()),
            'builds' => count($user->getBuilds()),
            'pushes' => count($user->getPushes())
        ]);

        $response->setBody($rendered);
    }
}
