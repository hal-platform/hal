<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Permissions;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\Entity\UserPermission;
use Hal\Core\Type\UserPermissionEnum;
use Hal\UI\Security\UserAuthorizations;

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
     * @param UserAuthorizations $currentUserPerms
     * @param UserPermission $removingType
     *
     * @return bool
     */
    private function isRemovalAllowed(UserAuthorizations $currentUserPerms, UserPermission $removingType)
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
                ->getRepository(UserPermission::class)
                ->findBy(['type' => UserPermissionEnum::TYPE_SUPER]);

            // Can only remove supers if at least 2 supers exist.
            if ($type === UserPermissionEnum::TYPE_SUPER && count($supers) < 2) {
                $this->reason = $this->ERR_NOPE_THE_LAST_SUPER;

                return false;
            }

            return true;
        }

        // Admin can do this
        if ($currentUserPerms->isAdmin()) {

            $pushers = $this->removalEM
                ->getRepository(UserPermission::class)
                ->findBy(['type' => UserPermissionEnum::TYPE_ADMIN]);

            // Cannot remove supers
            if ($type === UserPermissionEnum::TYPE_SUPER) {
                $this->reason = $this->ERR_NOPE_BTN;

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
