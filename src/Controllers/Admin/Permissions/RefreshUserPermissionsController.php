<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Controllers\Admin\Permissions;

use QL\Hal\Core\Entity\User;
use QL\Hal\Flasher;
use QL\Hal\Service\PermissionService;
use QL\Panthor\ControllerInterface;

class RefreshUserPermissionsController implements ControllerInterface
{
    const SUCCESS = 'Permission Cache refreshed for "%s".';

    /**
     * @type User
     */
    private $selectedUser;

    /**
     * @type PermissionService
     */
    private $permissions;

    /**
     * @type Flasher
     */
    private $flasher;

    /**
     * @param User $selectedUser
     * @param PermissionService $permissions
     * @param Flasher $flasher
     */
    public function __construct(
        User $selectedUser,
        PermissionService $permissions,
        Flasher $flasher
    ) {
        $this->selectedUser = $selectedUser;

        $this->permissions = $permissions;
        $this->flasher = $flasher;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $this->permissions->clearUserCache($this->selectedUser);

        return $this->flasher
            ->withFlash(sprintf(self::SUCCESS, $this->selectedUser->handle()))
            ->load('user', ['user' => $this->selectedUser->id()]);
    }
}
