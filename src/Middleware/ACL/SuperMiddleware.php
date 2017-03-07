<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Middleware\ACL;

use Hal\UI\Service\PermissionService;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\User;

/**
 * Note: Supers also pass this middleware bouncer.
 */
class SuperMiddleware extends AbstractPermissionMiddleware
{
    /**
     * @inheritDoc
     */
    protected function isAllowed(ServerRequestInterface $request, PermissionService $permissions, User $user): bool
    {
        $permissions = $permissions->getUserPermissions($user);

        if ($permissions->isSuper()) {
            return true;
        }

        return false;
    }
}
