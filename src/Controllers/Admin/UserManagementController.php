<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Admin;

use Doctrine\ORM\EntityManager;
use MCP\Corp\Account\LdapService;
use MCP\Corp\Account\User;
use QL\Hal\Core\Entity\Repository\UserRepository;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Display all current users in HAL and show their LDAP and HAL status.
 */
class UserManagementController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type UserRepository
     */
    private $repository;

    /**
     * @type LdapService
     */
    private $ldap;

    /**
     * Used for autopruning removed users.
     *
     * @type EntityManager
     */
    private $entityManager;

    /**
     * @type Request
     */
    private $request;

    /**
     * @type Response
     */
    private $response;

    /**
     * @param TemplateInterface $template
     * @param UserRepository $repository
     * @param LdapService $ldap
     * @param EntityManager $entityManager
     * @param Request $request
     * @param Response $response
     */
    public function __construct(
        TemplateInterface $template,
        UserRepository $repository,
        LdapService $ldap,
        EntityManager $entityManager,
        Request $request,
        Response $response
    ) {
        $this->template = $template;
        $this->repository = $repository;
        $this->ldap = $ldap;
        $this->entityManager = $entityManager;

        $this->request = $request;
        $this->response = $response;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $users = $this->repository->findBy([], ['isActive' => 'DESC', 'name' => 'ASC']);

        $parsed = [];
        foreach ($users as $user) {
            $ldapUser = $this->ldap->getUserByWindowsUsername($user->getHandle());
            $parsed[] = [
                'user' => $user,
                'hal_active' => $user->isActive(),
                'ldap_active' => ($ldapUser instanceof User)
            ];
        }

        $context = [
            'users' => $parsed
        ];

        if ($this->request->get('prune')) {
            if ($prunedUsers = $this->autoPrune($parsed)) {
                $context['pruned'] = $prunedUsers;
            } else {
                $context['no_pruned'] = true;
            }
        }

        $rendered = $this->template->render($context);
        $this->response->setBody($rendered);
    }

    /**
     * @param array $users
     * @return null
     */
    private function autoPrune(array $users)
    {
        $pruned = [];

        foreach ($users as $user) {
            if (!$user['hal_active']) {
                continue;
            }

            if (!$user['ldap_active']) {
                $pruned[] = $user['user']->getHandle();
                $user['user']->setIsActive(false);
                $this->entityManager->merge($user['user']);
            }
        }

        if ($pruned) {
            $this->entityManager->flush();
        }

        return $pruned;
    }

}
