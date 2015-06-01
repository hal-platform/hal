<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Admin\Permissions;

use QL\Hal\Core\Entity\User;
use QL\Hal\Flasher;
use QL\Hal\Service\PermissionsService;
use QL\Panthor\ControllerInterface;

class RefreshUserPermissionsController implements ControllerInterface
{
    const SUCCESS = 'Permission Cache refreshed for "%s".';

    /**
     * @type User
     */
    private $selectedUser;

    /**
     * @type PermissionsService
     */
    private $permissions;

    /**
     * @type Flasher
     */
    private $flasher;

    /**
     * @param User $selectedUser
     * @param PermissionsService $permissions
     * @param Flasher $flasher
     */
    public function __construct(
        User $selectedUser,
        PermissionsService $permissions,
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
