<?php

namespace QL\Hal\Api\Normalizer;

use QL\Hal\Api\Utility\HypermediaLinkTrait;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\User;
use QL\Hal\Service\PermissionsService;

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
            ['api.user', ['id' => $user->id()]],
            [
                'title' => $user->handle()
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
                'id' => $user->id(),
                'handle' => $user->handle(),
                'name' => $user->name(),
                'email' => $user->email(),
                'picture' => $user->pictureUrl(),
                'isActive' => $user->isActive(),
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
