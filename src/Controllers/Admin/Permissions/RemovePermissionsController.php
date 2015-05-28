<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Admin\Permissions;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Repository;
use QL\Hal\Core\Entity\User;
use QL\Hal\Core\Entity\UserType;
use QL\Hal\Flasher;
use QL\Hal\Service\NewPermissionsService;
use QL\Hal\Service\UserPerm;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

/**
 * Super:
 *     Add any.
 *     Remove Lead, ButtonPusher
 *
 * ButtonPusher:
 *     Add Lead, ButtonPusher
 *     Remove Lead
 *
 */
class RemovePermissionsController implements ControllerInterface
{
    const ERR_NOPE_SUPER = 'HAL Administrators cannot remove other HAL Administrators from the frontend.';
    const ERR_NOPE_BTN = 'Deployment Administrators cannot remove other Deployment Administrators from the frontend.';

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type User
     */
    private $currentUser;

    /**
     * @type UserType
     */
    private $userType;

    /**
     * @type EntityRepository
     */
    private $repoRepo;

    /**
     * @type NewPermissionsService
     */
    private $permissions;

    /**
     * @type Flasher
     */
    private $flasher;

    /**
     * @param TemplateInterface $template
     * @param User $currentUser
     * @param UserType $userType
     * @param EntityManagerInterface $em
     * @param NewPermissionsService $permissions
     * @param Flasher $flasher
     */
    public function __construct(
        TemplateInterface $template,
        User $currentUser,
        UserType $userType,
        EntityManagerInterface $em,
        NewPermissionsService $permissions,
        Flasher $flasher
    ) {
        $this->template = $template;
        $this->currentUser = $currentUser;
        $this->userType = $userType;

        $this->permissions = $permissions;
        $this->repoRepo = $em->getRepository(Repository::CLASS);
        $this->flasher = $flasher;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $currentUserPerms = $this->permissions->getUserPermissions($this->currentUser);
        $affectedUserPerms = $this->permissions->getUserPermissions($this->userType->user());
        $leadApps = $this->getLeadApplications($affectedUserPerms);

        $type = $this->userType->type();

        // Super can do this
        if ($currentUserPerms->isSuper()) {
            // super cannot remove super, must be done from DB
            if (!in_array($type, ['pleb', 'lead', 'btn_pusher'])) {
                return $this->flasher
                    ->withFlash('Access Denied', 'error', self::ERR_NOPE_SUPER)
                    ->load('admin.permissions');
            }

        // Button Pusher can do this
        } elseif ($currentUserPerms->isButtonPusher()) {
            // btn_pusher cannot remove super or btn_pusher
            if (!in_array($type, ['pleb', 'lead'])) {
                return $this->flasher
                    ->withFlash('Access Denied', 'error', self::ERR_NOPE_BTN)
                    ->load('admin.permissions');
            }
        }

        $rendered = $this->template->render([
            'userType' => $this->userType,
            'userPerm' => $affectedUserPerms,
            'leadApplications' => $leadApps
        ]);
    }

    /**
     * @param UserPerm $perm
     *
     * @return Repository[]
     */
    private function getLeadApplications(UserPerm $perm)
    {
        if (!$perm->isLead()) {
            return [];
        }

        if (!$perm->applications()) {
            return [];
        }

        $criteria = (new Criteria)->where(Criteria::expr()->in('id', $perm->applications()));
        $applications = $this->repoRepo->matching($criteria);

        return $applications->toArray();
    }
}
