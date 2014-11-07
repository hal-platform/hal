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
use QL\Hal\Services\PermissionsService;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

/**
 * User Edit Controller
 *
 * @package QL\Hal\Controllers\User
 */
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
    private $ldapUser;

    /**
     *  @param Twig_Template $template
     *  @param Layout $layout
     *  @param PermissionsService $permissions
     *  @param LdapService $ldap
     *  @param UserRepository $userRepo
     *  @param EntityManager $em
     *  @param UrlHelper $url
     *  @param LdapUser $ldapUser
     */
    public function __construct(
        Twig_Template $template,
        Layout $layout,
        PermissionsService $permissions,
        LdapService $ldap,
        UserRepository $userRepo,
        EntityManager $em,
        UrlHelper $url,
        LdapUser $ldapUser
    ) {
        $this->template = $template;
        $this->layout = $layout;
        $this->permissions = $permissions;
        $this->ldap = $ldap;
        $this->userRepo = $userRepo;
        $this->em = $em;
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
            // the answer is no
            return $this->url->redirectFor('denied');
        }

        $rendered = $this->layout->render($this->template, [
            'user' => $user,
            'ldapUser' => $this->ldap->getUserByCommonId($id),
            'pushes' => $this->getPushCount($user),
            'builds' => $this->getBuildCount($user)
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
        $dql = 'SELECT count(p.id) FROM QL\Hal\Core\Entity\Push p WHERE p.user = :user';
        $query = $this->em->createQuery($dql)
            ->setParameter('user', $user);

        return $query->getSingleScalarResult();
    }

    /**
     * Get the number of builds for a given user entity
     *
     * @param User $user
     * @return mixed
     */
    private function getBuildCount(User $user)
    {
        $dql = 'SELECT count(b.id) FROM QL\Hal\Core\Entity\Build b WHERE b.user = :user';
        $query = $this->em->createQuery($dql)
            ->setParameter('user', $user);

        return $query->getSingleScalarResult();
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
