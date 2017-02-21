<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Admin\Permissions;

use Doctrine\ORM\EntityManagerInterface;
use Hal\UI\Flasher;
use Hal\UI\Service\PermissionService;
use QL\Hal\Core\Entity\User;
use QL\Hal\Core\Entity\UserType;
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
    // This trait requires $this->em
    use RemovalPermissionsTrait;

    const SUCCESS = 'User Permission "%s" revoked from "%s".';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var User
     */
    private $currentUser;

    /**
     * @var UserType
     */
    private $userType;

    /**
     * @var PermissionService
     */
    private $permissions;

    /**
     * @var Flasher
     */
    private $flasher;

    /**
     * @param EntityManagerInterface $em
     * @param User $currentUser
     * @param UserType $userType
     * @param PermissionService $permissions
     * @param Flasher $flasher
     */
    public function __construct(
        EntityManagerInterface $em,
        User $currentUser,
        UserType $userType,
        PermissionService $permissions,
        Flasher $flasher
    ) {
        $this->em = $em;
        $this->currentUser = $currentUser;
        $this->userType = $userType;

        $this->permissions = $permissions;
        $this->flasher = $flasher;
    }

    /**
     * @inheritDoc
     */
    public function __invoke()
    {
        $currentUserPerms = $this->permissions->getUserPermissions($this->currentUser);

        if (!$this->isRemovalAllowed($currentUserPerms, $this->userType)) {
            $this->flasher->withFlash('Access Denied', 'error', $this->getRemovalDeniedReason());
            return $this->flasher->load('admin.permissions');
        }

        $map = [
            'pleb' => 'Standard',
            'lead' => 'Lead',
            'btn_pusher' => 'Admin',
            'super' => 'Super'
        ];

        $type = $this->userType->type();
        $type = $map[$type];
        $name = $this->userType->user()->handle();

        $this->permissions->removeUserPermissions($this->userType);

        $this->flasher
            ->withFlash(sprintf(self::SUCCESS, $type, $name), 'success')
            ->load('admin.permissions');
    }
}
