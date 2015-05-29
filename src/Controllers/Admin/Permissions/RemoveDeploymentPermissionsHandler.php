<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Admin\Permissions;

use QL\Hal\Core\Entity\UserPermission;
use QL\Hal\Flasher;
use QL\Hal\Service\NewPermissionsService;
use QL\Panthor\ControllerInterface;

class RemoveDeploymentPermissionsHandler implements ControllerInterface
{
    const SUCCESS = 'User Permissions for "%s" revoked from "%s".';

    /**
     * @type UserPermission
     */
    private $userPermission;

    /**
     * @type NewPermissionsService
     */
    private $permissions;

    /**
     * @type Flasher
     */
    private $flasher;

    /**
     * @param UserPermission $userPermission
     * @param NewPermissionsService $permissions
     * @param Flasher $flasher
     */
    public function __construct(
        UserPermission $userPermission,
        NewPermissionsService $permissions,
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
        $app = $this->userPermission->application()->getName();
        $name = $this->userPermission->user()->getHandle();

        $this->permissions->removeUserPermissions($this->userPermission);

        $this->flasher
            ->withFlash(sprintf(self::SUCCESS, $app, $name), 'success')
            ->load('admin.permissions.deployment');
    }
}
