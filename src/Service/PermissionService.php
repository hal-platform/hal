<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Service;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\Application;
use Hal\Core\Entity\Environment;
use Hal\Core\Entity\User;
use Hal\Core\Entity\UserPermission;
use QL\MCP\Cache\CachingTrait;
use QL\Panthor\Utility\JSON;

class PermissionService
{
    use CachingTrait;

    const CACHE_PERM = 'permissions:hal.%s';
    const CACHE_COLLAB = 'permissions:github.%s.%s';

    // Cached in memory only
    const CACHE_CAN_BUILD = 'permissions:hal.build.%s.%s';
    const CACHE_CAN_PUSH = 'permissions:hal.push.%s.%s.%s';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var EntityRepository
     */
    private $userTypeRepo;
    private $userPermissionsRepo;
    private $applicationRepo;

    /**
     * @var JSON
     */
    private $json;

    /**
     * Simple in-memory cache
     *
     * @var array
     */
    private $internalCache;

    /**
     * @param EntityManagerInterface $em
     * @param JSON $json
     */
    public function __construct(EntityManagerInterface $em, JSON $json)
    {
        $this->em = $em;
        $this->userPermissionsRepo = $em->getRepository(UserPermission::CLASS);
        $this->applicationRepo = $em->getRepository(Application::CLASS);

        $this->json = $json;

        $this->internalCache = [];
    }

    /**
     * @param User $user
     *
     * @return UserPerm
     */
    public function getUserPermissions(User $user)
    {
        $key = sprintf(self::CACHE_PERM, $user->id());

        // internal cache
        if (null !== ($cached = $this->getFromInternalCache($key))) {
            return $cached;
        }

        // external cache
        if ($result = $this->getFromCache($key)) {
            $decoded = $this->json->decode($result);

            if (is_array($decoded)) {
                return UserPerm::fromSerialized($decoded);
            }
        }

        $userTypes = $this->userTypesRepo->findBy(['user' => $user]);
        $userPermissions = $this->userPermissionsRepo->findBy(['user' => $user]);
        $perm = $this->parseUserPermissions($userTypes, $userPermissions);

        $this->setToInternalCache($key, $perm);
        $this->setToCache($key, $this->json->encode($perm));

        return $perm;
    }

    /**
     * @param UserType|UserPermission $permission
     *
     * @return void
     */
    public function removeUserPermissions($permission)
    {
        if ($permission instanceof UserType || $permission instanceof UserPermission) {

            $this->clearUserCache($permission->user());

            $this->em->remove($permission);
            $this->em->flush();
        }
    }

    /**
     * @param User $user
     *
     * @return void
     */
    public function clearUserCache(User $user)
    {
        $key = sprintf(self::CACHE_PERM, $user->id());
        $this->setToCache($key, null);
    }

    /**
     * @param User $user
     * @param Application $application
     *
     * @return bool
     */
    public function canUserBuild(User $user, Application $application)
    {
        $key = sprintf(self::CACHE_CAN_BUILD, $user->id(), $application->id());

        // internal cache
        if (null !== ($cached = $this->getFromInternalCache($key))) {
            return $cached;
        }

        $perm = $this->getUserPermissions($user);

        if ($perm->isButtonPusher() || $perm->isSuper()) {
            return $this->setToInternalCache($key, true);
        }

        if ($perm->isLead() && $perm->isLeadOfApplication($application)) {
            return $this->setToInternalCache($key, true);
        }

        if ($perm->canDeployApplicationToNonProd($application)) {
            return $this->setToInternalCache($key, true);
        }

        return $this->setToInternalCache($key, false);
    }

    /**
     * @param User $user
     * @param Application $application
     * @param Environment $environment
     *
     * @return bool
     */
    public function canUserPush(User $user, Application $application, Environment $environment)
    {
        $key = sprintf(self::CACHE_CAN_PUSH, $user->id(), $application->id(), $environment->id());

        // internal cache
        if (null !== ($cached = $this->getFromInternalCache($key))) {
            return $cached;
        }

        $perm = $this->getUserPermissions($user);

        // Not prod? Same permissions as building
        if (!$environment->isProduction()) {
            return $this->canUserBuild($user, $application);
        }

        if ($perm->isButtonPusher()) {
            return $this->setToInternalCache($key, true);
        }

        if ($perm->isSuper() && $this->isSuperApplication($application)) {
            return $this->setToInternalCache($key, true);
        }

        if ($perm->canDeployApplicationToProd($application)) {
            return $this->setToInternalCache($key, true);
        }

        return $this->setToInternalCache($key, false);
    }

    /**
     * Get a list of resolved applications sorted into "lead", "prod", "non_prod".
     *
     * This is not cached, and should be used sparingly. NEVER in a loop.
     *
     * @param UserPerm $perm
     *
     * @return array
     */
    public function getApplications(UserPerm $perm)
    {
        $apps = [];
        foreach ($perm->leadApplications() as $app) $apps[$app] = $app;
        foreach ($perm->prodApplications() as $app) $apps[$app] = $app;
        foreach ($perm->nonProdApplications() as $app) $apps[$app] = $app;

        $criteria = (new Criteria)->where(Criteria::expr()->in('id', $apps));
        $applications = $this->applicationRepo->matching($criteria);

        $appPerm = [
            'lead' => [],
            'prod' => [],
            'non_prod' => []
        ];

        foreach ($applications as $app) {
            if ($perm->isLeadOfApplication($app)) $appPerm['lead'][] = $app;
            if ($perm->canDeployApplicationToProd($app)) $appPerm['prod'][] = $app;
            if ($perm->canDeployApplicationToNonProd($app)) $appPerm['non_prod'][] = $app;
        }

        return $appPerm;
    }

    /**
     * @param UserType[] $types
     * @param UserPermission[] $permissions
     *
     * @return UserPerm
     */
    private function parseUserPermissions(array $types, array $permissions)
    {
        $parsed = [
            'isPleb' => false,
            'isLead' => false,
            'isButtonPusher' => false,
            'isSuper' => false,
            'leadApplications' => [],
            'prodApplications' => [],
            'nonProdApplications' => []
        ];

        // types
        foreach ($types as $t) {
            if ($t->type() === 'lead') {
                $parsed['isLead'] = true;

                if ($t->application()) {
                    $parsed['leadApplications'][$t->application()->id()] = $t->application()->id();
                }

            } elseif ($t->type() === 'btn_pusher') {
                $parsed['isButtonPusher'] = true;

            } elseif ($t->type() === 'super') {
                $parsed['isSuper'] = true;

            } elseif ($t->type() === 'pleb') {
                $parsed['isPleb'] = true;
            }
        }

        // permissions
        foreach ($permissions as $perm) {
            $key = ($perm->isProduction()) ? 'prodApplications' : 'nonProdApplications';
            $parsed[$key][$perm->application()->id()] = $perm->application()->id();
        }

        $perm = (new UserPerm($parsed['isPleb'], $parsed['isLead'], $parsed['isButtonPusher'], $parsed['isSuper']))
            ->withLeadApplications(array_values($parsed['leadApplications']))
            ->withProdApplications(array_values($parsed['prodApplications']))
            ->withNonProdApplications(array_values($parsed['nonProdApplications']));

        return $perm;
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    private function getFromInternalCache($key)
    {
        if (array_key_exists($key, $this->internalCache)) {
            return $this->internalCache[$key];
        }

        return null;
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return mixed
     */
    private function setToInternalCache($key, $value)
    {
        return $this->internalCache[$key] = $value;
    }
}
