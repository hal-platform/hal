<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\User;

use Doctrine\ORM\EntityManager;
use MCP\Corp\Account\LdapService;
use QL\Hal\Core\Entity\Repository\UserRepository;
use QL\Hal\Layout;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

/**
 *  Users Controller
 *
 *  @author Matt Colf <matthewcolf@quickenloans.com>
 */
class UsersController
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
     *  @var UserRepository
     */
    private $userRepo;

    /**
     * User for autopruning removed users.
     *
     *  @var EntityManager
     */
    private $entityManager;

    /**
     * User for autopruning removed users.
     *
     * @var LdapService
     */
    private $ldap;

    /**
     *  @param Twig_Template $template
     *  @param Layout $layout
     *  @param UserRepository $userRepo
     *  @param EntityManager $entityManager
     *  @param LdapService $ldap
     */
    public function __construct(
        Twig_Template $template,
        Layout $layout,
        UserRepository $userRepo,
        EntityManager $entityManager,
        LdapService $ldap
    ) {
        $this->template = $template;
        $this->layout = $layout;
        $this->userRepo = $userRepo;
        $this->entityManager = $entityManager;
        $this->ldap = $ldap;
    }

    /**
     *  @param Request $request
     *  @param Response $response
     */
    public function __invoke(Request $request, Response $response)
    {
        $users = $this->userRepo->findBy([], ['name' => 'ASC']);

        $active = [];
        $inactive = [];

        foreach ($users as $user) {
            if ($user->isActive()) {
                $active[] = $user;
            } else {
                $inactive[] = $user;
            }
        }

        $context = [
            'users' => $active,
            'inactiveUsers' => $inactive
        ];

        if ($request->get('prune') && $prunedUsers = $this->autoPrune($active)) {
            $context['pruned'] = $prunedUsers;
        }

        $rendered = $this->layout->render($this->template, $context);
        $response->body($rendered);
    }

    /**
     * @param array $users
     * @return null
     */
    private function autoPrune(array $users)
    {
        $pruned = [];

        foreach ($users as $user) {
            if (!$ldapUser = $this->ldap->getUserByWindowsUsername($user->getHandle())) {
                $pruned[] = $user->getHandle();
                $user->setIsActive(false);
                $this->entityManager->merge($user);
            }
        }

        if ($pruned) {
            $this->entityManager->flush();
        }

        return $pruned;
    }
}
