<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Middleware\ACL;

use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Service\PermissionService;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\User;

class AdminOrSelfMiddleware extends AbstractPermissionMiddleware
{
    use SessionTrait;

    /**
     * @inheritDoc
     */
    protected function isAllowed(ServerRequestInterface $request, PermissionService $permissions, User $user): bool
    {
        $permissions = $permissions->getUserPermissions($user);

        if ($permissions->isButtonPusher() || $permissions->isSuper()) {
            return true;
        }

        $currentUser = $this->getUser($request);
        if ($user === $currentUser) {
            return true;
        }

        return false;
    }
}
