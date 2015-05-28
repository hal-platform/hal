<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Twig;

use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\Repository;
use QL\Hal\Core\Entity\User;
use QL\Hal\Service\NewPermissionsService;
use Twig_Extension;
use Twig_SimpleFunction;

class PermissionsExtension extends Twig_Extension
{
    const NAME = 'hal_permissions';

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
            new Twig_SimpleFunction('isUserStandard', [$this, 'isUserStandard']),
            new Twig_SimpleFunction('isUserLead', [$this, 'isUserLead']),
            new Twig_SimpleFunction('isUserAdmin', [$this, 'isUserAdmin']),
            new Twig_SimpleFunction('isUserSuper', [$this, 'isUserSuper']),

            new Twig_SimpleFunction('isUserAdminOrSuper', [$this, 'isUserAdminOrSuper']),
            new Twig_SimpleFunction('isUserLeadOf', [$this, 'isUserLeadOf']),

            new Twig_SimpleFunction('canUserBuild', [$this, 'canUserBuild']),
            new Twig_SimpleFunction('canUserPush', [$this, 'canUserPush']),
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
     * @param Repository|null $application
     *
     * @return bool
     */
    public function isUserLeadOf($user, $application)
    {
        if (!$perm = $this->getUserPerms($user)) {
            return false;
        }

        if (!$perm->isLead()) {
            return false;
        }

        if (!$application instanceof Repository) {
            return false;
        }

        $apps = $perm->applications();

        return in_array($application->getId(), $apps, true);
    }

    /**
     * @param User|null $user
     * @param Repository|null $application
     *
     * @return bool
     */
    public function canUserBuild($user, $application)
    {
        if (!$user instanceof User) return false;
        if (!$application instanceof Repository) return false;

        return $this->permissions->canUserBuild($user, $application);
    }

    /**
     * @param User|null $user
     * @param Repository|null $application
     * @param Environment|null $application
     *
     * @return bool
     */
    public function canUserPush($user, $application, $environment)
    {
        if (!$user instanceof User) return false;
        if (!$application instanceof Repository) return false;
        if (!$environment instanceof Environment) return false;

        return $this->permissions->canUserPush($user, $application, $environment);
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
