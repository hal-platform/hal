<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Admin\Permissions;

use QL\Hal\Core\Entity\UserPermission;
use QL\Hal\Flasher;
use QL\Hal\Service\PermissionsService;
use QL\Panthor\ControllerInterface;

class RemoveDeploymentPermissionsHandler implements ControllerInterface
{
    const SUCCESS = 'User Permissions for "%s" revoked from "%s".';

    /**
     * @type UserPermission
     */
    private $userPermission;

    /**
     * @type PermissionsService
     */
    private $permissions;

    /**
     * @type Flasher
     */
    private $flasher;

    /**
     * @param UserPermission $userPermission
     * @param PermissionsService $permissions
     * @param Flasher $flasher
     */
    public function __construct(
        UserPermission $userPermission,
        PermissionsService $permissions,
        Flasher $flasher
    ) {
        $this->userPermission = $userPermission;

        $this->permissions = $permissions;
        $this->flasher = $flasher;
    }

    /**
     * {@inheritdoc}
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
