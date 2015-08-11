<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken;

use QL\Hal\Core\Entity\User;
use QL\Kraken\Core\Entity\Application;
use QL\Kraken\Core\Entity\Environment;
use QL\Kraken\Service\PermissionService;
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
     * @param KrakenPermissionService $krakenPermissions
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
     * @param Environment $environment
     *
     * @see self::denied
     */
    public function requireDeployPermissions(Application $application, Environment $environment)
    {
        $canDeploy = $this->permissions->canUserDeploy($this->currentUser, $application, $environment);

        if ($canDeploy) {
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
