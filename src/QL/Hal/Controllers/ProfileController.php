<?php

namespace QL\Hal\Controllers;

use Twig_Template;
use Slim\Http\Request;
use Slim\Http\Response;
use QL\Hal\Layout;
use QL\Hal\PushPermissionService;
use MCP\Corp\Account\LdapService;
use MCP\Corp\Account\User;
use QL\Hal\Core\Entity\Repository\UserRepository;
use Doctrine\ORM\EntityManager;

/**
 *  Profile Controller
 *
 *  @author Matt Colf <matthewcolf@quickenloans.com>
 */
class ProfileController
{
    const LDAP_USER = 'placeholder';

    const LDAP_PASSWORD = 'placeholder';

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
     *  @var User
     */
    private $user;

    /**
     *  @var EntityManager
     */
    private $em;

    /**
     *  @param Twig_Template $template
     *  @param Layout $layout
     *  @param PushPermissionService $permissions
     *  @param LdapService $ldap
     *  @param UserRepository $userRepo
     *  @param User $user
     *  @param EntityManager $em
     */
    public function __construct(
        Twig_Template $template,
        Layout $layout,
        PushPermissionService $permissions,
        LdapService $ldap,
        UserRepository $userRepo,
        User $user,
        EntityManager $em
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
     *  @param Request $request
     *  @param Response $response
     *  @param array $params
     *  @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = null, callable $notFound = null)
    {
        // default to current user
        $id = (isset($params['id'])) ? $params['id'] : $this->user->commonId();
        $user = $this->userRepo->findOneBy(['id' => $id]);

        if (!$user) {
            call_user_func($notFound);
            return;
        }

        // refactor ldap authentication... @todo
        $this->ldap->authenticate(self::LDAP_USER, self::LDAP_PASSWORD, false);

        $response->body(
            $this->layout->render(
                $this->template,
                [
                    'user' => $user,
                    'ldapUser' => $this->ldap->getUserByCommonId($id),
                    'permissions' => $this->permissions->repoEnvsCommonIdCanPushTo($id),
                    'pushes' => $this->getPushCount($user)
                ]
            )
        );
    }

    /**
     *  Get the number of pushes for a given user entity
     *
     *  @param $user
     *  @return mixed
     */
    private function getPushCount($user)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('count(p.id)');
        $qb->from('QL\Hal\Core\Entity\Push', 'p');
        $qb->where('p.user = :user');
        $qb->setParameter('user', $user);

        return $qb->getQuery()->getSingleScalarResult();
    }
}
