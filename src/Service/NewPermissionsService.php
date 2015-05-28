<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use MCP\Cache\CachingTrait;
use QL\Hal\Core\Entity\User;
use QL\Hal\Core\Entity\UserType;
use QL\Hal\Core\Entity\UserPermission;
use QL\Panthor\Utility\Json;

class NewPermissionsService
{
    use CachingTrait;

    const CACHE_PERM = 'permissions:hal.%s';
    const CACHE_COLLAB = 'permissions:github.%s.%s';

    /**
     * @type EntityManagerInterface
     */
    private $em;

    /**
     * @type EntityRepository
     */
    private $userTypeRepo;

    /**
     * @type GitHubService
     */
    private $github;

    /**
     * @type Json
     */
    private $json;

    /**
     * Simple in-memory cache
     *
     * @type array
     */
    private $internalCache;

    private $productionEnvironments;
    private $superApplications;

    /**
     * @param EntityManagerInterface $em
     * @param GitHubService $github
     * @param Json $json
     */
    public function __construct(
        EntityManagerInterface $em,
        GitHubService $github,
        Json $json
    ) {
        $this->em = $em;
        $this->userTypesRepo = $em->getRepository(UserType::CLASS);

        $this->github = $github;
        $this->json = $json;

        $this->internalCache = [];

        $this->productionEnvironments = ['prod', 'production'];
        $this->superApplications = [
            'hal9000',
            'hal9000-agent',
            'eternia-cloud'
        ];
    }

    /**
     * @param User $user
     *
     * @return UserPerm
     */
    public function getUserPermissions(User $user)
    {
        $key = sprintf(self::CACHE_PERM, $user->getId());

        // internal cache
        if (array_key_exists($key, $this->internalCache)) {
            return $this->internalCache[$key];
        }

        // external cache
        if ($result = $this->getFromCache($key)) {
            $decoded = $this->json->decode($result);

            if (is_array($decoded)) {
                return UserPerm::fromSerialized($decoded);
            }
        }

        $userTypes = $this->userTypesRepo->findBy(['user' => $user]);
        $perm = $this->parseUserTypes($userTypes);

        $this->internalCache[$key] = $perm;
        $this->setToCache($key, $this->json->encode($perm));
        return $perm;
    }

    /**
     * @param UserType $permission
     *
     * @return void
     */
    public function removeUserPermissions(UserType $permission)
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
        $key = sprintf(self::CACHE_PERM, $user->getId());
        $this->setToCache($key, null);
    }

    /**
     * @param User $user
     * @param Repository $application
     *
     * @return bool
     */
    public function canUserBuild(User $user, Repository $application)
    {
        $perm = $user->getUserPermissions($user);

        if ($perm->isButtonPusher() || $perm->isSuper()) {
            return true;
        }

        if ($perm->isLead() && in_array($repository->getId(), $perm->applications(), true)) {
            return true;
        }

        if ($this->isUserCollaborator($user, $application)) {
            return true;
        }

        // @todo, add deployment permissions here

        return false;
    }

    /**
     * @param User $user
     * @param Repository $application
     * @param Environment $environment
     *
     * @return bool
     */
    public function canUserPush(User $user, Repository $application, Environment $environment)
    {
        $perm = $user->getUserPermissions($user);

        // Not prod? Same permissions as building
        if (!$this->isEnvironmentProduction($environment)) {
            return $this->canUserBuild($user, $application);
        }

        if ($perm->isButtonPusher()) {
            return true;
        }

        if ($perm->isSuper() && $this->isSuperApplication($application)) {
            return true;
        }

        // @todo, add deployment permissions here

        return false;
    }

    /**
     * @todo replace with db toggle
     *
     * @param string $environment
     * @return bool
     */
    private function isEnvironmentProduction($environment)
    {
        return in_array($environment, $this->productionEnvironments);
    }

    /**
     * @param Repository $application
     *
     * @return bool
     */
    private function isSuperApplication(Repository $application)
    {
        return in_array($application->getKey(), $this->superApplications);
    }

    /**
     * @param User $user
     * @param Repository $application
     *
     * @return bool
     */
    private function isUserCollaborator(User $user, Repository $application)
    {
        $key = sprintf(self::CACHE_COLLAB, $user->getId(), $application->getKey());

        // internal cache
        if (array_key_exists($key, $this->internalCache)) {
            return $this->internalCache[$key];
        }

        // external cache
        if ($result = $this->getFromCache($key)) {
            return $result;
        }

        $result = $this->github->isUserCollaborator(
            $application->getGithubUser(),
            $application->getGithubRepo(),
            $user->getHandle()
        );

        $this->setToCache($key, $result);
        $this->internalCache[$key] = $result;
        return $result;
    }

    /**
     * @param UserType[] $types
     *
     * @return UserPerm
     */
    private function parseUserTypes(array $types)
    {
        $parsed = [
            'isPleb' => false,
            'isLead' => false,
            'isButtonPusher' => false,
            'isSuper' => false,
            'applications' => []
        ];

        foreach ($types as $t) {
            if ($t->type() === 'lead') {
                $parsed['isLead'] = true;

                if ($t->application()) {
                    $parsed['applications'][$t->application()->getId()] = $t->application()->getId();
                }

            } elseif ($t->type() === 'btn_pusher') {
                $parsed['isButtonPusher'] = true;

            } elseif ($t->type() === 'super') {
                $parsed['isSuper'] = true;

            } elseif ($t->type() === 'pleb') {
                $parsed['isPleb'] = true;
            }
        }

        $parsed['applications'] = array_values($parsed['applications']);

        return new UserPerm(
            $parsed['isPleb'],
            $parsed['isLead'],
            $parsed['isButtonPusher'],
            $parsed['isSuper'],
            $parsed['applications']
        );
    }
}
