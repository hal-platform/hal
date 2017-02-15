<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Twig;

use Hal\UI\Service\PermissionService;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\User;
use Twig_Extension;
use Twig_SimpleFunction;

class PermissionsExtension extends Twig_Extension
{
    const NAME = 'hal_permissions';

    /**
     * @var PermissionService
     */
    private $permissions;

    /**
     * @param PermissionService $permissions
     */
    public function __construct(PermissionService $permissions)
    {
        $this->permissions = $permissions;
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @inheritDoc
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
     * @param Application|null $application
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

        if (!$application instanceof Application) {
            return false;
        }

        return $perm->isLeadOfApplication($application);
    }

    /**
     * @param User|null $user
     * @param Application|null $application
     *
     * @return bool
     */
    public function canUserBuild($user, $application)
    {
        if (!$user instanceof User) return false;
        if (!$application instanceof Application) return false;

        return $this->permissions->canUserBuild($user, $application);
    }

    /**
     * @param User|null $user
     * @param Application|null $application
     * @param Environment|null $application
     *
     * @return bool
     */
    public function canUserPush($user, $application, $environment)
    {
        if (!$user instanceof User) return false;
        if (!$application instanceof Application) return false;
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
