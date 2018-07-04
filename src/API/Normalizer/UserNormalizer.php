<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\API\Normalizer;

use Hal\UI\API\Hyperlink;
use Hal\UI\API\HypermediaResource;
use Hal\UI\API\ResourceNormalizerInterface;
use Hal\Core\Entity\User;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Security\AuthorizationService;

class UserNormalizer implements ResourceNormalizerInterface
{
    use SessionTrait;

    /**
     * @var AuthorizationService
     */
    private $authorizationService;

    /**
     * @param AuthorizationService $authorizationService
     */
    public function __construct(AuthorizationService $authorizationService)
    {
        $this->authorizationService = $authorizationService;
    }

    /**
     * @param User $input
     *
     * @return mixed
     */
    public function normalize($input)
    {
        return $this->resource($input);
    }

    /**
     * @param User|null $user
     *
     * @return Hyperlink|null
     */
    public function link($user): ?Hyperlink
    {
        if (!$user instanceof User) {
            return null;
        }

        return new Hyperlink(
            ['api.user', ['user' => $user->id()]],
            $user->name()
        );
    }

    /**
     * @param User|null $user
     *
     * @return HypermediaResource|null
     */
    public function resource($user, array $embed = []): ?HypermediaResource
    {
        if (!$user instanceof User) {
            return null;
        }

        $authorizations = $this->authorizationService->getUserAuthorizations($user);

        $data = [
            'id' => $user->id(),
            'name' => $user->name(),
            // 'email' => $user->email(),
            'is_disabled' => $user->isDisabled(),
            'permissions' => [
                'member' => $authorizations->isMember(),
                'owner' => $authorizations->isOwner(),
                'admin' => $authorizations->isAdmin(),
                'super' => $authorizations->isSuper(),
            ],
        ];

        $links = [
            'self' => $this->link($user),
        ];

        $resource = new HypermediaResource($data, $links);

        $resource->withEmbedded($embed);

        return $resource;
    }
}
