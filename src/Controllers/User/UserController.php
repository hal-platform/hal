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
use QL\Hal\Core\Entity\Repository\TokenRepository;
use QL\Hal\Core\Entity\User;
use QL\Hal\Core\Entity\Repository\UserRepository;
use QL\Hal\Layout;
use QL\Hal\PushPermissionService;
use QL\Hal\Services\PermissionsService;
use QL\HttpProblem\HttpProblemException;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

/**
 *  Profile Controller
 *
 *  @author Matt Colf <matthewcolf@quickenloans.com>
 */
class UserController
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
     * @var TokenRepository
     */
    private $tokens;

    /**
     *  @var LdapUser
     */
    private $ldapUser;

    /**
     *  @var EntityManager
     */
    private $em;

    /**
     * @param Twig_Template $template
     * @param Layout $layout
     * @param LdapService $ldap
     * @param UserRepository $userRepo
     * @param TokenRepository $tokens
     * @param LdapUser $ldapUser
     * @param EntityManager $em
     * @param PermissionsService $permissions
     */
    public function __construct(
        Twig_Template $template,
        Layout $layout,
        LdapService $ldap,
        UserRepository $userRepo,
        TokenRepository $tokens,
        LdapUser $ldapUser,
        EntityManager $em,
        PermissionsService $permissions
    ) {
        $this->template = $template;
        $this->layout = $layout;
        $this->permissions = $permissions;
        $this->ldap = $ldap;
        $this->userRepo = $userRepo;
        $this->tokens = $tokens;
        $this->ldapUser = $ldapUser;
        $this->em = $em;
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

        // If current user, load tokens
        $tokens = [];
        if ($id == $this->ldapUser->commonId()) {
            $tokens = $this->tokens->findBy(['user' => $user]);
        }

        $rendered = $this->layout->render($this->template, [
            'tokens' => $tokens,
            'user' => $user,
            'ldapUser' => $this->ldap->getUserByCommonId($id),
            'permissions' => $this->permissions->userPushPermissionPairs($user->getHandle()),
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
}
