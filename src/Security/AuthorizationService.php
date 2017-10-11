<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Security;

// use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
// use Hal\Core\Entity\Application;
// use Hal\Core\Entity\Environment;
use Hal\Core\Entity\User;
use Hal\Core\Entity\UserPermission;
use Hal\Core\Type\UserPermissionEnum;
use QL\MCP\Cache\CachingTrait;
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
    private $userPermissionsRepo;
    private $applicationRepo;

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
        $this->permissionsRepo = $em->getRepository(UserPermission::class);
        // $this->applicationRepo = $em->getRepository(Application::CLASS);

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

        $permissions = $this->permissionsRepo->findBy(['user' => $user]);
        $authorizations = $this->parseAuthorizations($permissions);

        $this->setToCache($key, $this->json->encode($authorizations));

        return $authorizations;
    }

    /**
     * @param UserPermission $permission
     *
     * @return void
     */
    public function removeUserPermissions(UserPermission $permission)
    {
        $this->clearUserCache($permission->user());

        $this->em->remove($permission);
        $this->em->flush();
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
                'application_id' => $application,
                'organization_id' => $organization,
                'environment_id' => $environment
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
