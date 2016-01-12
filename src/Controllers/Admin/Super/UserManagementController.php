<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
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

/**
 * Display all current users in HAL and show their LDAP and HAL status.
 */
class UserManagementController implements ControllerInterface
{
    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var LdapService
     */
    private $ldap;

    /**
     * Used for autopruning removed users.
     *
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var EntityRepository
     */
    private $userRepo;

    /**
     * @var Request
     */
    private $request;

    /**
     * @param TemplateInterface $template
     * @param LdapService $ldap
     * @param EntityManagerInterface $em
     * @param Request $request
     */
    public function __construct(
        TemplateInterface $template,
        LdapService $ldap,
        EntityManagerInterface $em,
        Request $request
    ) {
        $this->template = $template;
        $this->ldap = $ldap;

        $this->userRepo = $em->getRepository(User::CLASS);
        $this->em = $em;

        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $users = $this->userRepo->findBy([], ['isActive' => 'DESC', 'name' => 'ASC']);

        $parsed = [];
        foreach ($users as $user) {
            $ldapUser = $this->ldap->getUserByWindowsUsername($user->handle());
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

        $this->template->render($context);
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
                $pruned[] = $user['user']->handle();
                $user['user']->withIsActive(false);
                $this->em->merge($user['user']);
            }
        }

        if ($pruned) {
            $this->em->flush();
        }

        return $pruned;
    }
}
