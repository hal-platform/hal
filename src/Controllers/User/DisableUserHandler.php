<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Controllers\User;

use Doctrine\ORM\EntityManagerInterface;
use QL\Hal\Core\Entity\User;
use QL\Hal\Flasher;
use QL\Hal\Service\PermissionService;
use QL\Panthor\ControllerInterface;

class DisableUserHandler implements ControllerInterface
{
    const SUCCESS = 'User Disabled.';
    const ERR_THANKS_FOR_ASKING = 'Hal Administrators cannot be disabled.';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var PermissionService
     */
    private $permissions;

    /**
     * @var Flasher
     */
    private $flasher;

    /**
     * @var User
     */
    private $selectedUser;

    /**
     * @param EntityManagerInterface $em
     * @param PermissionService $permissions
     * @param Flasher $flasher
     * @param User $selectedUser
     */
    public function __construct(
        EntityManagerInterface $em,
        PermissionService $permissions,
        Flasher $flasher,
        User $selectedUser
    ) {
        $this->em = $em;
        $this->permissions = $permissions;

        $this->flasher = $flasher;
        $this->selectedUser = $selectedUser;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $permissions = $this->permissions->getUserPermissions($this->selectedUser);

        if ($permissions->isSuper()) {
            $this->flasher
                ->withFlash(self::ERR_THANKS_FOR_ASKING, 'error', ' Remove this level of permission before disabling this user.')
                ->load('user', ['user' => $this->selectedUser->id()]);
        }

        $this->selectedUser
            ->withIsActive(false);

        $this->em->merge($this->selectedUser);
        $this->em->flush();

        $this->flasher
            ->withFlash(self::SUCCESS, 'success')
            ->load('user', ['user' => $this->selectedUser->id()]);
    }
}
