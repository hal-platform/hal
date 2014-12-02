<?php

namespace QL\Hal\Api\Normalizer;

use QL\Hal\Api\Utility\HypermediaLinkTrait;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\User;

/**
 * User Object Normalizer
 */
class UserNormalizer
{
    use HypermediaLinkTrait;
    use HypermediaResourceTrait;

    /**
     * @param User $user
     * @return array
     */
    public function link(User $user)
    {
        return $this->buildLink(
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
    public function resource(User $user)
    {
        return $this->buildResource(
            [
                'id' => $user->getId(),
                'handle' => $user->getHandle(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'picture' => $user->getPictureUrl()
            ],
            [],
            [
                'self' => $this->link($user),
                'permissions' => $this->buildLink(['api.user.permissions', ['id' => $user->getId()]])
            ]
        );
    }
}