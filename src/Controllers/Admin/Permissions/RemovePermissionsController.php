<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Controllers\Admin\Permissions;

use QL\Hal\Core\Entity\User;
use QL\Hal\Core\Entity\UserType;
use QL\Hal\Flasher;
use QL\Hal\Service\PermissionService;
use QL\Hal\Service\UserPerm;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

/**
 * Super:
 *     Add any.
 *     Remove Lead, ButtonPusher
 *
 * ButtonPusher:
 *     Add Lead, ButtonPusher
 *     Remove Lead
 *
 */
class RemovePermissionsController implements ControllerInterface
{
    const ERR_NOPE_SUPER = 'HAL Administrators cannot remove other HAL Administrators from the frontend.';
    const ERR_NOPE_BTN = 'Deployment Administrators cannot remove other Deployment Administrators from the frontend.';

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type User
     */
    private $currentUser;

    /**
     * @type UserType
     */
    private $userType;

    /**
     * @type PermissionService
     */
    private $permissions;

    /**
     * @type Flasher
     */
    private $flasher;

    /**
     * @param TemplateInterface $template
     * @param User $currentUser
     * @param UserType $userType
     * @param EntityManagerInterface $em
     * @param PermissionService $permissions
     * @param Flasher $flasher
     */
    public function __construct(
        TemplateInterface $template,
        User $currentUser,
        UserType $userType,
        PermissionService $permissions,
        Flasher $flasher
    ) {
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

        if (!$this->isAllowed($currentUserPerms)) {
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

    /**
     * Is the current user allowed to do this?
     *
     * @param UserPerm $currentUserPerms
     *
     * @return bool
     */
    private function isAllowed(UserPerm $currentUserPerms)
    {
        $type = $this->userType->type();

        // Super can do this
        if ($currentUserPerms->isSuper()) {
            // super cannot remove super, must be done from DB
            if (!in_array($type, ['pleb', 'lead', 'btn_pusher'])) {
                 $this->flasher->withFlash('Access Denied', 'error', self::ERR_NOPE_SUPER);
                 return false;
            }

            return true;

        // Button Pusher can do this
        } elseif ($currentUserPerms->isButtonPusher()) {
            // btn_pusher cannot remove super or btn_pusher
            if (!in_array($type, ['pleb', 'lead'])) {
                $this->flasher->withFlash('Access Denied', 'error', self::ERR_NOPE_BTN);
                return false;
            }

            return true;
        }

        return false;
    }
}
