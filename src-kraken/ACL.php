<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
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
     * @var TemplateInterface
     */
    private $denied;

    /**
     * @var Halt
     */
    private $halt;

    /**
     * @var PermissionService
     */
    private $permissions;

    /**
     * @var User
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
