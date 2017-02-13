<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Admin\Permissions;

use Hal\UI\Flasher;
use Hal\UI\Service\PermissionService;
use QL\Hal\Core\Entity\User;
use QL\Panthor\ControllerInterface;

class RefreshUserPermissionsController implements ControllerInterface
{
    const SUCCESS = 'Permission Cache refreshed for "%s".';

    /**
     * @var User
     */
    private $selectedUser;

    /**
     * @var PermissionService
     */
    private $permissions;

    /**
     * @var Flasher
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
