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
    // const CACHE_LDAP_GROUP = 'permissions:ldap.group.%s';
    // const CACHE_LDAP_USER = 'permissions:ldap.user.%s';

    /**
     * @type EntityRepository
     */
    // private $userRepo;
    private $userTypeRepo;

    /**
     * @type Json
     */
    private $json;

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em, Json $json)
    {
        $this->userTypesRepo = $em->getRepository(UserType::CLASS);
        $this->json = $json;
    }

    /**
     * @param User $user
     *
     * @return UserPerm
     */
    public function getUserPermissions(User $user)
    {
        $key = sprintf(self::CACHE_PERM, $user->getId());
        if ($result = $this->getFromCache($key)) {
            $decoded = $this->json->decode($result);

            if (is_array($decoded)) {
                return UserPerm::fromSerialized($decoded);
            }
        }

        $userTypes = $this->userTypesRepo->findBy(['user' => $user]);
        $parsed = $this->parseUserTypes($userTypes);
        $perm = new UserPerm(
            $parsed['isPleb'],
            $parsed['isLead'],
            $parsed['isButtonPusher'],
            $parsed['isSuper'],
            $parsed['applications']
        );

        $this->setToCache($key, $this->json->encode($perm));
        return $perm;
    }

    /**
     * @param UserType[] $types
     *
     * @return array
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

        return $parsed;
    }
}
