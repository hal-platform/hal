<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Admin\Super;

use Doctrine\ORM\EntityManagerInterface;
use QL\Hal\Core\Entity\User;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

/**
 * Display all current users in Hal and show their Hal status.
 */
class UserManagementController implements ControllerInterface
{
    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityRepository
     */
    private $userRepo;

    /**
     * @param TemplateInterface $template
     * @param LdapService $ldap
     * @param EntityManagerInterface $em
     * @param Request $request
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em
    ) {
        $this->template = $template;

        $this->userRepo = $em->getRepository(User::CLASS);
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $users = $this->userRepo->findBy([], ['isActive' => 'DESC', 'name' => 'ASC']);

        $parsed = [];
        foreach ($users as $user) {
            $parsed[] = [
                'user' => $user,
                'hal_active' => $user->isActive()
            ];
        }

        $context = [
            'users' => $parsed
        ];

        $this->template->render($context);
    }
}
