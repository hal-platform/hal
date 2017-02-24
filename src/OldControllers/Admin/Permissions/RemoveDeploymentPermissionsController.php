<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Admin\Permissions;

use Hal\UI\Service\PermissionService;
use Hal\UI\Service\UserPerm;
use QL\Hal\Core\Entity\UserPermission;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class RemoveDeploymentPermissionsController implements ControllerInterface
{
    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var UserPermission
     */
    private $userPermission;

    /**
     * @var PermissionService
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
     * @inheritDoc
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