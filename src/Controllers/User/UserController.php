<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\User;

use MCP\Corp\Account\LdapService;
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
     * @param Twig_Template $template
     * @param LdapService $ldap
     * @param UserRepository $userRepo
     * @param PermissionsService $permissions
     */
    public function __construct(
        Twig_Template $template,
        LdapService $ldap,
        UserRepository $userRepo,
        PermissionsService $permissions
    ) {
        $this->template = $template;
        $this->ldap = $ldap;
        $this->userRepo = $userRepo;
        $this->permissions = $permissions;
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
        $id = $params['id'];

        if (!$user = $this->userRepo->find($id)) {
            return call_user_func($notFound);
        }

        $rendered = $this->template->render([
            'user' => $user,
            'ldapUser' => $this->ldap->getUserByCommonId($user->getId()),
            'permissions' => $this->permissions->userPushPermissionPairs($user->getHandle()),
            'builds' => count($user->getBuilds()),
            'pushes' => count($user->getPushes())
        ]);

        $response->setBody($rendered);
    }
}
