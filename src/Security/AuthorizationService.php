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
use Hal\Core\Utility\CachingTrait;
use QL\Panthor\Utility\JSON;

class AuthorizationService
{
    use CachingTrait;

    const CACHE_PERM = 'permissions.hal.%s';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var EntityRepository
     */
    private $userPermissionsRepository;

    /**
     * @var JSON
     */
    private $json;

    /**
     * @param EntityManagerInterface $em
     * @param JSON $json
     */
    public function __construct(EntityManagerInterface $em, JSON $json)
    {
        $this->em = $em;
        $this->userPermissionsRepository = $em->getRepository(UserPermission::class);

        $this->json = $json;
    }

    /**
     * @param User $user
     *
     * @return UserAuthorizations
     */
    public function getUserAuthorizations(User $user): UserAuthorizations
    {
        $key = $this->cacheKey($user);

        // external cache
        if ($result = $this->getFromCache($key)) {
            $decoded = $this->json->decode($result);

            if (is_array($decoded)) {
                return UserAuthorizations::fromSerialized($decoded);
            }
        }

        $permissions = $this->userPermissionsRepository->findBy(['user' => $user]);
        $authorizations = $this->parseAuthorizations($permissions);

        $this->setToCache($key, $this->json->encode($authorizations));

        return $authorizations;
    }

    /**
     * @param UserPermission $permission
     * @param bool $delayFlush
     *
     * @return void
     */
    public function addUserPermissions(UserPermission $permission, $delayFlush = false)
    {
        $this->em->persist($permission);

        if (!$delayFlush) {
            $this->em->flush();
        }

        $this->clearUserCache($permission->user());
    }

    /**
     * @param UserPermission $permission
     * @param bool $delayFlush
     *
     * @return void
     */
    public function removeUserPermissions(UserPermission $permission, $delayFlush = false)
    {
        $this->em->remove($permission);

        if (!$delayFlush) {
            $this->em->flush();
        }

        $this->clearUserCache($permission->user());
    }

    /**
     * @param User $user
     *
     * @return void
     */
    public function clearUserCache(User $user)
    {
        $key = $this->cacheKey($user);
        $this->setToCache($key, null);
    }

    /**
     * @param UserPermission[] $permissions
     *
     * @return UserAuthorizations
     */
    private function parseAuthorizations(array $permissions): UserAuthorizations
    {
        $tiers = [];
        foreach (UserPermissionEnum::options() as $type) {
            $tiers[$type] = [];
        }

        foreach ($permissions as $permission) {
            // sanity check
            if (!isset($tiers[$permission->type()])) {
                continue;
            }

            $hash = UserAuthorizations::hash($permission->application(), $permission->organization(), $permission->environment());

            $tiers[$permission->type()][$hash] = [
                'permission_id' => $permission->id(),
                'application_id' => $permission->application() ? $permission->application()->id() : null,
                'organization_id' => $permission->organization() ? $permission->organization()->id() : null,
                'environment_id' => $permission->environment() ? $permission->environment()->id() : null,
            ];
        }

        return new UserAuthorizations($tiers);
    }

    /**
     * @param User $user
     *
     * @return string
     */
    private function cacheKey(User $user)
    {
        return sprintf(self::CACHE_PERM, $user->id());
    }
}
