<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Api\Normalizer;

use Hal\UI\Api\Hyperlink;
use Hal\UI\Api\NormalizerInterface;
use Hal\UI\Api\Utility\HypermediaResourceTrait;
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
     * @return array
     */
    public function normalize($input)
    {
        return $this->resource($input);
    }

    /**
     * @param User $user
     * @return array
     */
    public function link(User $user = null)
    {
        if (!$user) {
            return null;
        }

        return new Hyperlink(
            ['api.user', ['id' => $user->id()]],
            $user->handle()
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
