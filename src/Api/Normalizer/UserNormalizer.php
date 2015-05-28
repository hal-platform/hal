<?php

namespace QL\Hal\Api\Normalizer;

use QL\Hal\Api\Utility\HypermediaLinkTrait;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\User;
use QL\Hal\Service\NewPermissionsService;

/**
 * User Object Normalizer
 */
class UserNormalizer
{
    use HypermediaLinkTrait;
    use HypermediaResourceTrait;

    /**
     * @var NewPermissionsService
     */
    private $permissions;

    /**
     * @param NewPermissionsService $permissions
     */
    public function __construct(
        NewPermissionsService $permissions
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

        $perm = $this->permissions->getUserPermissions($user);

        return $this->buildResource(
            [
                'id' => $user->getId(),
                'handle' => $user->getHandle(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'picture' => $user->getPictureUrl(),
                'permissions' => [
                    'standard' => $perm->isPleb(),
                    'lead' => $perm->isLead(),
                    'admin' => $perm->isButtonPusher(),
                    'super' => $perm->isSuper()
                ]
            ],
            [],
            [
                'self' => $this->link($user)
            ]
        );
    }
}
