<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Admin\Super;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use MCP\Corp\Account\LdapService;
use MCP\Corp\Account\User as LdapUser;
use QL\Hal\Core\Entity\User;
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
     * @type LdapService
     */
    private $ldap;

    /**
     * Used for autopruning removed users.
     *
     * @type EntityManagerInterface
     */
    private $em;

    /**
     * @type EntityRepository
     */
    private $userRepo;

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
     * @param LdapService $ldap
     * @param EntityManagerInterface $em
     * @param Request $request
     * @param Response $response
     */
    public function __construct(
        TemplateInterface $template,
        LdapService $ldap,
        EntityManagerInterface $em,
        Request $request,
        Response $response
    ) {
        $this->template = $template;
        $this->ldap = $ldap;

        $this->userRepo = $em->getRepository(User::CLASS);
        $this->em = $em;

        $this->request = $request;
        $this->response = $response;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $users = $this->userRepo->findBy([], ['isActive' => 'DESC', 'name' => 'ASC']);

        $parsed = [];
        foreach ($users as $user) {
            $ldapUser = $this->ldap->getUserByWindowsUsername($user->getHandle());
            $parsed[] = [
                'user' => $user,
                'hal_active' => $user->isActive(),
                'ldap_active' => ($ldapUser instanceof LdapUser)
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
                $this->em->merge($user['user']);
            }
        }

        if ($pruned) {
            $this->em->flush();
        }

        return $pruned;
    }
}
