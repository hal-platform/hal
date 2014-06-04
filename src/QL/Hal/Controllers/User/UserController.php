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
use QL\Hal\Core\Entity\User;
use QL\Hal\Core\Entity\Repository\UserRepository;
use QL\Hal\Layout;
use QL\Hal\PushPermissionService;
use QL\Hal\Services\PermissionsService;
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
     *  @var LdapUser
     */
    private $user;

    /**
     *  @var EntityManager
     */
    private $em;

    /**
     * @param Twig_Template $template
     * @param Layout $layout
     * @param LdapService $ldap
     * @param UserRepository $userRepo
     * @param LdapUser $user
     * @param EntityManager $em
     * @param PermissionsService $permissions
     */
    public function __construct(
        Twig_Template $template,
        Layout $layout,
        LdapService $ldap,
        UserRepository $userRepo,
        LdapUser $user,
        EntityManager $em,
        PermissionsService $permissions
    ) {
        $this->template = $template;
        $this->layout = $layout;
        $this->permissions = $permissions;
        $this->ldap = $ldap;
        $this->userRepo = $userRepo;
        $this->user = $user;
        $this->em = $em;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @param callable $notFound
     * @return mixed
     */
    public function __invoke(Request $request, Response $response, array $params = null, callable $notFound = null)
    {
        // default to current user
        $id = (isset($params['id'])) ? $params['id'] : $this->user->commonId();
        if (!$user = $this->userRepo->findOneBy(['id' => $id])) {
            return call_user_func($notFound);
        }

        $rendered = $this->layout->render($this->template, [
            'profileUser' => $user,
            'ldapUser' => $this->ldap->getUserByCommonId($id),
            'permissions' => $this->permissions->userPermissionPairs($user->getHandle()),
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
        $dql = 'SELECT count(p.id) FROM QL\Hal\Core\Entity\Push p WHERE p.user = :user';
        $query = $this->em->createQuery($dql)
            ->setParameter('user', $user);

        return $query->getSingleScalarResult();
    }
}
