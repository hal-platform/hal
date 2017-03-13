<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\API\Normalizer;

use Hal\UI\API\Hyperlink;
use Hal\UI\API\HypermediaResource;
use Hal\UI\API\NormalizerInterface;
use Hal\UI\Service\PermissionService;
use QL\Hal\Core\Entity\User;

class UserNormalizer implements NormalizerInterface
{
    /**
     * @var PermissionService
     */
    private $permissions;

    /**
     * @param PermissionService $permissions
     */
    public function __construct(PermissionService $permissions)
    {
        $this->permissions = $permissions;
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
            $user->handle()
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

        $perm = $this->permissions->getUserPermissions($user);

        $data = [
            'id' => $user->id(),
            'username' => $user->handle(),
            'name' => $user->name(),
            'email' => $user->email(),
            'is_disabled' => !$user->isActive(),
            'permissions' => [
                'standard' => $perm->isPleb(),
                'lead' => $perm->isLead(),
                'admin' => $perm->isButtonPusher(),
                'super' => $perm->isSuper()
            ]
        ];

        $links = [
            'self' => $this->link($user)
        ];

        $resource = new HypermediaResource($data, $links);

        $resource->withEmbedded($embed);

        return $resource;
    }
}
