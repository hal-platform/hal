<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\API\Normalizer;

use Hal\UI\API\Hyperlink;
use Hal\UI\API\NormalizerInterface;
use Hal\UI\API\Utility\HypermediaResourceTrait;
use Hal\UI\Service\PermissionService;
use QL\Hal\Core\Entity\User;

class UserNormalizer implements NormalizerInterface
{
    use HypermediaResourceTrait;

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
     * @return array|null
     */
    public function normalize($input)
    {
        return $this->resource($input);
    }

    /**
     * @param User $user
     *
     * @return Hyperlink|null
     */
    public function link(User $user = null): ?Hyperlink
    {
        if (!$user) {
            return null;
        }

        return new Hyperlink(
            ['api.user', ['user' => $user->id()]],
            $user->handle()
        );
    }

    /**
     * @param User $user
     *
     * @return array|null
     */
    public function resource(User $user = null)
    {
        if (is_null($user)) {
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

        $embedded = [];

        $links = ['self' => $this->link($user)];

        return $this->buildResource($data, $embedded, $links);
    }
}
