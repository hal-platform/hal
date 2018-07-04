<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Security;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\User;
use Hal\Core\Entity\User\UserPermission;
use Hal\Core\Type\UserPermissionEnum;

class AuthorizationHydrator
{
    /**
     * @var EntityRepository
     */
    private $permissionsRepo;

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->permissionsRepo = $em->getRepository(UserPermission::class);
    }

    /**
     * Get a list of resolved authorizations sorted into their authorization level.
     *
     * This is not cached, and should be used sparingly. NEVER in a loop.
     *
     * @param User $user
     * @param UserAuthorizations $authorizations
     *
     * @return array
     */
    public function hydrateAuthorizations(User $user, UserAuthorizations $authorizations)
    {
        $tiers = [];
        foreach (UserPermissionEnum::options() as $type) {
            $tiers[$type] = [];
        }

        $perm = $tiers;

        foreach ($tiers as $type => $x) {
            $tiers[$type] = $authorizations->getAuthorizationsFor($type);
        }

        $permissions = $this->permissionsRepo->findBy(['user' => $user]);
        foreach ($permissions as $permission) {
            $hash = UserAuthorizations::hash($permission->application(), $permission->organization(), $permission->environment());
            $type = $permission->type();

            if (isset($tiers[$type][$hash]) && isset($perm[$type])) {
                $perm[$type][] = $permission;
            }
        }

        return $perm;
    }
}
