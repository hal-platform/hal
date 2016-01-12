<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Controllers\Admin\Permissions;

use QL\Hal\Core\Entity\UserPermission;
use QL\Hal\Service\PermissionService;
use QL\Hal\Service\UserPerm;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class RemoveDeploymentPermissionsController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type UserPermission
     */
    private $userPermission;

    /**
     * @type PermissionService
     */
    private $permissions;

    /**
     * @param TemplateInterface $template
     * @param UserPermission $userPermission
     * @param PermissionService $permissions
     */
    public function __construct(
        TemplateInterface $template,
        UserPermission $userPermission,
        PermissionService $permissions
    ) {
        $this->template = $template;
        $this->userPermission = $userPermission;

        $this->permissions = $permissions;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $selectedUserPerms = $this->permissions->getUserPermissions($this->userPermission->user());
        $appPerm = $this->permissions->getApplications($selectedUserPerms);

        $rendered = $this->template->render([
            'userPermission' => $this->userPermission,
            'userPerm' => $selectedUserPerms,

            'leadApplications' => $appPerm['lead'],
            'prodApplications' => $appPerm['prod'],
            'nonProdApplications' => $appPerm['non_prod'],
        ]);
    }
}
