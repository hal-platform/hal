<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Admin\Permissions;

use Hal\UI\Flasher;
use Hal\UI\Service\PermissionService;
use QL\Hal\Core\Entity\UserPermission;
use QL\Panthor\ControllerInterface;

class RemoveDeploymentPermissionsHandler implements ControllerInterface
{
    const SUCCESS = 'User Permissions for "%s" revoked from "%s".';

    /**
     * @var UserPermission
     */
    private $userPermission;

    /**
     * @var PermissionService
     */
    private $permissions;

    /**
     * @var Flasher
     */
    private $flasher;

    /**
     * @param UserPermission $userPermission
     * @param PermissionService $permissions
     * @param Flasher $flasher
     */
    public function __construct(
        UserPermission $userPermission,
        PermissionService $permissions,
        Flasher $flasher
    ) {
        $this->userPermission = $userPermission;

        $this->permissions = $permissions;
        $this->flasher = $flasher;
    }

    /**
     * @inheritDoc
     */
    public function __invoke()
    {
        $app = $this->userPermission->application()->name();
        $name = $this->userPermission->user()->handle();

        $this->permissions->removeUserPermissions($this->userPermission);

        $this->flasher
            ->withFlash(sprintf(self::SUCCESS, $app, $name), 'success')
            ->load('admin.permissions.deployment');
    }
}