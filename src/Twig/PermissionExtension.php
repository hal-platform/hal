<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Twig;

use QL\Hal\Services\PermissionsService;
use Twig_Extension;
use Twig_SimpleFunction;

class PermissionExtension extends Twig_Extension
{
    const NAME = 'hal_permissions';

    /**
     * @type PermissionsService
     */
    private $permissions;

    /**
     * @param PermissionsService $permissions
     */
    public function __construct(PermissionsService $permissions)
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
            new Twig_SimpleFunction('canUserPush', [$this->permissions, 'allowPush']),
            new Twig_SimpleFunction('canUserBuild', [$this->permissions, 'allowBuild']),
            new Twig_SimpleFunction('isUserAdmin', [$this->permissions, 'allowAdmin']),
            new Twig_SimpleFunction('isUserRepoAdmin', [$this->permissions, 'allowRepoAdmin']),
            new Twig_SimpleFunction('isUserSuperAdmin', [$this->permissions, 'allowSuperAdmin']),

            // other
            new Twig_SimpleFunction('showAnalytics', [$this->permissions, 'showAnalytics'])
        ];
    }
}
