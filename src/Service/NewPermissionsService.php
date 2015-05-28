<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
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

    /**
     * @type EntityManagerInterface
     */
    private $em;

    /**
     * @type EntityRepository
     */
    private $userTypeRepo;

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

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em, Json $json)
    {
        $this->em = $em;
        $this->userTypesRepo = $em->getRepository(UserType::CLASS);
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
