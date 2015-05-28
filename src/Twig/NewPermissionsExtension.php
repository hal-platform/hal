<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Twig;

use QL\Hal\Core\Entity\Repository;
use QL\Hal\Core\Entity\User;
use QL\Hal\Service\NewPermissionsService;
use Twig_Extension;
use Twig_SimpleFunction;

class NewPermissionsExtension extends Twig_Extension
{
    const NAME = 'hal_new_permissions';

    /**
     * @type NewPermissionsService
     */
    private $permissions;

    /**
     * @param NewPermissionsService $permissions
     */
    public function __construct(NewPermissionsService $permissions)
    {
        $this->permissions = $permissions;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            // permissions
            new Twig_SimpleFunction('isUserStandard', [$this, 'isUserStandard']),
            new Twig_SimpleFunction('isUserLead', [$this, 'isUserLead']),
            new Twig_SimpleFunction('isUserAdmin', [$this, 'isUserAdmin']),
            new Twig_SimpleFunction('isUserSuper', [$this, 'isUserSuper']),

            new Twig_SimpleFunction('isUserAdminOrSuper', [$this, 'isUserAdminOrSuper']),
            new Twig_SimpleFunction('isUserLeadOf', [$this, 'isUserLeadOf']),
        ];
    }

    /**
     * @param User|null $user
     *
     * @return bool
     */
    public function isUserStandard($user)
    {
        if (!$perm = $this->getUserPerms($user)) {
            return false;
        }

        return $perm->isPleb();
    }

    /**
     * @param User|null $user
     *
     * @return bool
     */
    public function isUserLead($user)
    {
        if (!$perm = $this->getUserPerms($user)) {
            return false;
        }

        return $perm->isLead();
    }

    /**
     * @param User|null $user
     *
     * @return bool
     */
    public function isUserAdmin($user)
    {
        if (!$perm = $this->getUserPerms($user)) {
            return false;
        }

        return $perm->isButtonPusher();
    }

    /**
     * @param User|null $user
     *
     * @return bool
     */
    public function isUserSuper($user)
    {
        if (!$perm = $this->getUserPerms($user)) {
            return false;
        }

        return $perm->isSuper();
    }

    /**
     * @param User|null $user
     *
     * @return bool
     */
    public function isUserAdminOrSuper($user)
    {
        return $this->isUserAdmin($user) || $this->isUserSuper($user);
    }

    /**
     * @param User|null $user
     * @param Repository|null $repository
     *
     * @return bool
     */
    public function isUserLeadOf($user, $repository)
    {
        if (!$perm = $this->getUserPerms($user)) {
            return false;
        }

        if (!$perm->isLead()) {
            return false;
        }

        if (!$repository instanceof Repository) {
            return false;
        }

        $apps = $perm->applications();

        return in_array($repository->getId(), $apps, true);
    }

    /**
     * @param User|null $user
     *
     * @return UserPerm|null
     */
    private function getUserPerms($user)
    {
        if (!$user instanceof User) return null;
        return $this->permissions->getUserPermissions($user);
    }
}
