<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Controllers\Admin\Permissions;

use Doctrine\ORM\EntityManagerInterface;
use QL\Hal\Core\Entity\User;
use QL\Hal\Core\Entity\UserPermission;
use QL\Hal\Core\Entity\UserType;
use QL\Hal\Core\Type\EnumType\UserTypeEnum;
use QL\Hal\Flasher;
use QL\Hal\Service\PermissionService;
use QL\Hal\Service\UserPerm;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

/**
 * Super:
 *     Add any.
 *     Remove any.
 *         - If removing super, must be at least one super left.
 *
 * ButtonPusher:
 *     Add Lead, ButtonPusher
 *     Remove Lead, ButtonPusher
 *         - If removing ButtonPusher, must be at least one ButtonPusher left.
 *
 */
class RemovePermissionsController implements ControllerInterface
{
    // This trait requires $this->em
    use RemovalPermissionsTrait;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var User
     */
    private $currentUser;

    /**
     * @var UserType
     */
    private $userType;

    /**
     * @var PermissionService
     */
    private $permissions;

    /**
     * @var Flasher
     */
    private $flasher;

    /**
     * @param EntityManagerInterface $em
     * @param TemplateInterface $template
     * @param User $currentUser
     * @param UserType $userType
     * @param EntityManagerInterface $em
     * @param PermissionService $permissions
     * @param Flasher $flasher
     */
    public function __construct(
        EntityManagerInterface $em,
        TemplateInterface $template,
        User $currentUser,
        UserType $userType,
        PermissionService $permissions,
        Flasher $flasher
    ) {
        $this->em = $em;
        $this->template = $template;
        $this->currentUser = $currentUser;
        $this->userType = $userType;

        $this->permissions = $permissions;
        $this->flasher = $flasher;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $currentUserPerms = $this->permissions->getUserPermissions($this->currentUser);

        if (!$this->isRemovalAllowed($currentUserPerms, $this->userType)) {
            $this->flasher->withFlash('Access Denied', 'error', $this->getRemovalDeniedReason());
            return $this->flasher->load('admin.permissions');
        }

        $selectedUserPerms = $this->permissions->getUserPermissions($this->userType->user());
        $appPerm = $this->permissions->getApplications($selectedUserPerms);

        $rendered = $this->template->render([
            'userType' => $this->userType,
            'userPerm' => $selectedUserPerms,

            'leadApplications' => $appPerm['lead'],
            'prodApplications' => $appPerm['prod'],
            'nonProdApplications' => $appPerm['non_prod'],
        ]);
    }
}
