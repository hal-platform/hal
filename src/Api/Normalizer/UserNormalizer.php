<?php

namespace QL\Hal\Api\Normalizer;

use QL\Hal\Api\Utility\HypermediaLinkTrait;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\User;
use QL\Hal\Services\PermissionsService;

/**
 * User Object Normalizer
 */
class UserNormalizer
{
    use HypermediaLinkTrait;
    use HypermediaResourceTrait;

    /**
     * @var PermissionsService
     */
    private $permissions;

    /**
     * @param PermissionsService $permissions
     */
    public function __construct(
        PermissionsService $permissions
    ) {
        $this->permissions = $permissions;
    }

    /**
     * @param User $user
     * @return array
     */
    public function link(User $user = null)
    {
        return  (is_null($user)) ? null :$this->buildLink(
            ['api.user', ['id' => $user->getId()]],
            [
                'title' => $user->getHandle()
            ]
        );
    }

    /**
     * @param User $user
     * @return array
     */
    public function resource(User $user = null)
    {
        if (is_null($user)) {
            return null;
        }

        return $this->buildResource(
            [
                'id' => $user->getId(),
                'handle' => $user->getHandle(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'picture' => $user->getPictureUrl(),
                'permissions' => [
                    'admin' => $this->permissions->allowAdmin($user),
                    'superAdmin' => $this->permissions->allowSuperAdmin($user)
                ]
            ],
            [],
            [
                'self' => $this->link($user)
            ]
        );
    }
}