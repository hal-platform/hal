<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Admin;

use Doctrine\ORM\EntityManagerInterface;
use QL\Hal\Core\Entity\User;
use QL\Hal\Core\Entity\UserType;
use QL\Hal\Flasher;
use QL\Hal\Service\NewPermissionsService;
use QL\Panthor\ControllerInterface;

/**
 * Super:
 *     Add any.
 *     Remove Lead, ButtonPusher
 *
 * ButtonPusher:
 *     Add Lead, ButtonPusher
 *     Remove Lead
 */
class RemovePermissionsHandler implements ControllerInterface
{
    const SUCCESS = 'User Permission "%s" revoked from "%s".';
    const ERR_NOPE_SUPER = 'HAL Administrators cannot remove other HAL Administrators from the frontend.';
    const ERR_NOPE_BTN = 'Deployment Administrators cannot remove other Deployment Administrators from the frontend.';

    /**
     * @type User
     */
    private $currentUser;

    /**
     * @type UserType
     */
    private $userType;

    /**
     * @type NewPermissionsService
     */
    private $permissions;

    /**
     * @type Flasher
     */
    private $flasher;

    /**
     * @param User $currentUser
     * @param UserType $userType
     * @param EntityManagerInterface $em
     * @param NewPermissionsService $permissions
     * @param Flasher $flasher
     */
    public function __construct(
        User $currentUser,
        UserType $userType,
        EntityManagerInterface $em,
        NewPermissionsService $permissions,
        Flasher $flasher
    ) {
        $this->currentUser = $currentUser;
        $this->userType = $userType;

        $this->em = $em;
        $this->permissions = $permissions;
        $this->flasher = $flasher;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $currentUserPerms = $this->permissions->getUserPermissions($this->currentUser);

        $type = $this->userType->type();

        // Super can do this
        if ($currentUserPerms->isSuper()) {
            // super cannot remove super, must be done from DB
            if (!in_array($type, ['pleb', 'lead', 'btn_pusher'])) {
                return $this->flasher
                    ->withFlash('Access Denied', 'error', self::ERR_NOPE_SUPER)
                    ->load('admin.permissions');
            }

        // Button Pusher can do this
        } elseif ($currentUserPerms->isButtonPusher()) {
            // btn_pusher cannot remove super or btn_pusher
            if (!in_array($type, ['pleb', 'lead'])) {
                return $this->flasher
                    ->withFlash('Access Denied', 'error', self::ERR_NOPE_BTN)
                    ->load('admin.permissions');
            }
        }

        $map = [
            'pleb' => 'Standard',
            'lead' => 'Lead',
            'btn_pusher' => 'Admin',
            'super' => 'Super'
        ];

        $type = $map[$type];
        $name = $this->userType->user()->getHandle();

        $this->em->remove($this->userType);
        $this->em->flush();

        $this->flasher
            ->withFlash(sprintf(self::SUCCESS, $type, $name), 'success')
            ->load('admin.permissions');
    }
}
