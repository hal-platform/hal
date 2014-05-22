<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\User;

use Doctrine\ORM\EntityManager;
use MCP\Corp\Account\LdapService;
use MCP\Corp\Account\User as LdapUser;
use QL\Hal\Core\Entity\Repository\UserRepository;
use QL\Hal\Core\Entity\User;
use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Layout;
use QL\Hal\PushPermissionService;
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
     *  @var Layout
     */
    private $layout;

    /**
     *  @var PushPermissionService
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
     *  @var EntityManager
     */
    private $em;

    /**
     *  @var UrlHelper
     */
    private $url;

    /**
     *  @var LdapUser
     */
    private $currentUser;

    /**
     *  @param Twig_Template $template
     *  @param Layout $layout
     *  @param PushPermissionService $permissions
     *  @param LdapService $ldap
     *  @param UserRepository $userRepo
     *  @param EntityManager $em
     *  @param UrlHelper $url
     *  @param LdapUser $currentUser
     */
    public function __construct(
        Twig_Template $template,
        Layout $layout,
        PushPermissionService $permissions,
        LdapService $ldap,
        UserRepository $userRepo,
        EntityManager $em,
        UrlHelper $url,
        LdapUser $currentUser
    ) {
        $this->template = $template;
        $this->layout = $layout;
        $this->permissions = $permissions;
        $this->ldap = $ldap;
        $this->userRepo = $userRepo;
        $this->em = $em;
        $this->url = $url;
        $this->currentUser = $currentUser;
    }

    /**
     *  @param Request $request
     *  @param Response $response
     *  @param array $params
     *  @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = null, callable $notFound = null)
    {
        $id = $params['id'];
        if (!$user = $this->userRepo->findOneBy(['id' => $id])) {
            return call_user_func($notFound);
        }

        if (!$this->isUserAllowed($user)) {
            // the answer is no
            return $this->url->redirectFor('denied');
        }

        $rendered = $this->layout->render($this->template, [
            'profileUser' => $user,
            'ldapUser' => $this->ldap->getUserByCommonId($id),
            'permissions' => $this->permissions->repoEnvsCommonIdCanPushTo($id),
            'pushes' => $this->getPushCount($user)
        ]);

        $response->body($rendered);
    }

    /**
     *  Get the number of pushes for a given user entity
     *
     *  @param User $user
     *  @return mixed
     */
    private function getPushCount(User $user)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('count(p.id)');
        $qb->from('QL\Hal\Core\Entity\Push', 'p');
        $qb->where('p.user = :user');
        $qb->setParameter('user', $user);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Does the user have the correct permissions to access this page?
     *
     * @param User $user
     * @return boolean
     */
    private function isUserAllowed(User $user)
    {
        if ($this->permissions->isUserAdmin($this->currentUser)) {
            return true;
        }

        return ($this->currentUser->commonId() == $user->getId());
    }
}
