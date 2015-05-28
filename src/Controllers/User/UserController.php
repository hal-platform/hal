<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\User;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use MCP\Corp\Account\LdapService;
use QL\Hal\Core\Repository\UserRepository;
use QL\Hal\Core\Entity\Repository;
use QL\Hal\Core\Entity\User;
use QL\Hal\Service\NewPermissionsService;
use QL\Hal\Service\UserPerm;
use QL\Hal\Services\PermissionsService;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class UserController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type User
     */
    private $user;

    /**
     * @type UserRepository
     */
    private $userRepo;

    /**
     * @type EntityRepository
     */
    private $repoRepo;

    /**
     * @type LdapService
     */
    private $ldap;

    /**
     * @type NewPermissionsService
     */
    private $permissions;

    /**
     * @type PermissionsService
     */
    private $permissionsLegacy;

    /**
     * @param TemplateInterface $template
     * @param User $user
     * @param EntityManagerInterface $em
     *
     * @param LdapService $ldap
     * @param NewPermissionsService $permissions
     * @param PermissionsService $permissionsLegacy
     */
    public function __construct(
        TemplateInterface $template,
        User $user,
        EntityManagerInterface $em,
        LdapService $ldap,
        NewPermissionsService $permissions,
        PermissionsService $permissionsLegacy
    ) {
        $this->template = $template;
        $this->user = $user;

        $this->userRepo = $em->getRepository(User::CLASS);
        $this->repoRepo = $em->getRepository(Repository::CLASS);

        $this->ldap = $ldap;
        $this->permissions = $permissions;
        $this->permissionsLegacy = $permissionsLegacy;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $userPerm = $this->permissions->getUserPermissions($this->user);
        $leadApps = $this->getLeadApplications($userPerm);

        $rendered = $this->template->render([
            'user' => $this->user,
            'userPerm' => $userPerm,
            'leadApplications' => $leadApps,

            'ldapUser' => $this->ldap->getUserByCommonId($this->user->getId()),

            'permissions' => $this->permissionsLegacy->userPushPermissionPairs($this->user->getHandle()),
            'builds' => $this->userRepo->getBuildCount($this->user),
            'pushes' => $this->userRepo->getPushCount($this->user),
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
