<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Permissions;

use Doctrine\ORM\EntityManagerInterface;
use Hal\UI\Service\UserPerm;
use QL\Hal\Core\Entity\UserType;
use QL\Hal\Core\Type\EnumType\UserTypeEnum;

trait RemovalPermissionsTrait
{
    private $ERR_NOPE_THE_LAST_SUPER = 'There must be at least one Hal administrator in the system.';
    private $ERR_NOPE_BTN = 'Deployment Administrators cannot remove Hal Administrators.';
    private $ERR_NOPE_LAST_BTN = 'There must be at least one Deployment Administrators in the system.';

    private $reason = '';

    private $removalEM;

    /**
     * Is the current user allowed to do this?
     *
     * @param UserPerm $currentUserPerms
     * @param UserType $removingType
     *
     * @return bool
     */
    private function isRemovalAllowed(UserPerm $currentUserPerms, UserType $removingType)
    {
        $this->reason = '';

        if (!$this->removalEM) {
            $this->reason = 'Missing entity manager.';
            return false;
        }

        $type = $removingType->type();

        // Super can do this
        if ($currentUserPerms->isSuper()) {

            $supers = $this->removalEM
                ->getRepository(UserType::class)
                ->findBy(['type' => UserTypeEnum::TYPE_SUPER]);

            // Can only remove supers if at least 2 supers exist.
            if ($type === UserTypeEnum::TYPE_SUPER && count($supers) < 2) {
                 $this->reason = $this->ERR_NOPE_THE_LAST_SUPER;
                 return false;
            }

            return true;
        }

        // Button Pusher can do this
        if ($currentUserPerms->isButtonPusher()) {

            $pushers = $this->removalEM
                ->getRepository(UserType::class)
                ->findBy(['type' => UserTypeEnum::TYPE_ADMIN]);

            // Cannot remove supers
            if ($type === UserTypeEnum::TYPE_SUPER) {
                 $this->reason = $this->ERR_NOPE_BTN;
                return false;

            // Can only remove pushers if at least 2 pushers exist.
            } elseif ($type === UserTypeEnum::TYPE_ADMIN && count($pushers) < 2) {
                $this->reason = $this->ERR_NOPE_LAST_BTN;
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * @param EntityManagerInterface $em
     *
     * @return void
     */
    private function setEntityManagerForRemovalPermissions(EntityManagerInterface $em)
    {
        $this->removalEM = $em;
    }

    /**
     * @return string
     */
    private function getRemovalDeniedReason()
    {
        return $this->reason;
    }
}
