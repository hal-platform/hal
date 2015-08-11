<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal;

use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\User;
use QL\Hal\Service\PermissionService;
use QL\Panthor\Slim\Halt;
use QL\Panthor\TemplateInterface;

class ACL
{
    /**
     * @type TemplateInterface
     */
    private $denied;

    /**
     * @type Halt
     */
    private $halt;

    /**
     * @type PermissionService
     */
    private $permissions;

    /**
     * @type User
     */
    private $currentUser;

    /**
     * @param TemplateInterface $denied
     * @param Halt $halt
     * @param PermissionService $permissions
     * @param User $currentUser
     */
    public function __construct(
        TemplateInterface $denied,
        Halt $halt,
        PermissionService $permissions,
        User $currentUser
    ) {
        $this->denied = $denied;
        $this->halt = $halt;

        $this->permissions = $permissions;
        $this->currentUser = $currentUser;
    }

    /**
     * @see self::denied
     */
    public function requireAdmin()
    {
        $perm = $this->permissions->getUserPermissions($this->currentUser);

        if ($perm->isButtonPusher() || $perm->isSuper()) {
            return;
        }

        $this->denied();
    }

    /**
     * @param Application $application
     *
     * @see self::denied
     */
    public function requireLeadOrHigher(Application $application)
    {
        $perm = $this->permissions->getUserPermissions($this->currentUser);

        if ($perm->isLeadOfApplication($application) || $perm->isButtonPusher() || $perm->isSuper()) {
            return;
        }

        $this->denied();
    }

    /**
     * @throws Exception
     *
     * @return void
     */
    public function denied()
    {
        $rendered = $this->denied->render();

        call_user_func($this->halt, 403, $rendered);
    }
}
