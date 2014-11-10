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
use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Services\PermissionsService;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

class EditController
{
    /**
     *  @var Twig_Template
     */
    private $template;

    /**
     *  @var PermissionsService
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
     *  @var UrlHelper
     */
    private $url;

    /**
     *  @var LdapUser
     */
    private $ldapUser;

    /**
     *  @param Twig_Template $template
     *  @param PermissionsService $permissions
     *  @param LdapService $ldap
     *  @param UserRepository $userRepo
     *  @param UrlHelper $url
     *  @param LdapUser $ldapUser
     */
    public function __construct(
        Twig_Template $template,
        PermissionsService $permissions,
        LdapService $ldap,
        UserRepository $userRepo,
        UrlHelper $url,
        LdapUser $ldapUser
    ) {
        $this->template = $template;
        $this->permissions = $permissions;
        $this->ldap = $ldap;
        $this->userRepo = $userRepo;
        $this->url = $url;
        $this->ldapUser = $ldapUser;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @param callable $notFound
     * @return mixed|void
     */
    public function __invoke(Request $request, Response $response, array $params = null, callable $notFound = null)
    {
        $id = $params['id'];
        if (!$user = $this->userRepo->findOneBy(['id' => $id])) {
            return call_user_func($notFound);
        }

        if (!$this->isUserAllowed($user)) {
            return $this->url->redirectFor('denied');
        }

        $rendered = $this->template->render([
            'user' => $user,
            'ldapUser' => $this->ldap->getUserByCommonId($id),
            'pushes' => count($user->getPushes()),
            'builds' => count($user->getBuilds())
        ]);

        $response->setBody($rendered);
    }

    /**
     * Does the user have the correct permissions to access this page?
     *
     * @param User $user
     * @return boolean
     */
    private function isUserAllowed(User $user)
    {
        if ($this->permissions->allowAdmin($this->ldapUser)) {
            return true;
        }

        return ($this->currentUser->commonId() == $user->getId());
    }
}
