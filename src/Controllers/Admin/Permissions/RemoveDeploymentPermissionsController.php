<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Admin\Permissions;

use QL\Hal\Core\Entity\UserPermission;
use QL\Hal\Service\NewPermissionsService;
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
     * @type NewPermissionsService
     */
    private $permissions;

    /**
     * @param TemplateInterface $template
     * @param UserPermission $userPermission
     * @param NewPermissionsService $permissions
     */
    public function __construct(
        TemplateInterface $template,
        UserPermission $userPermission,
        NewPermissionsService $permissions
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
