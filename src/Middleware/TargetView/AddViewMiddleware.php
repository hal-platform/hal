<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Middleware\TargetView;

use Hal\UI\Middleware\ACL\AbstractPermissionMiddleware;
use Hal\UI\Service\PermissionService;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\User;

/**
 * Note: Admins and Supers also pass this middleware bouncer.
 */
class AddViewMiddleware extends AbstractPermissionMiddleware
{
    /**
     * @inheritDoc
     */
    protected function isAllowed(ServerRequestInterface $request, PermissionService $permissions, User $user): bool
    {
        $userPermissions = $permissions->getUserPermissions($user);

        // Allow if admin
        if ($userPermissions->isButtonPusher() || $userPermissions->isSuper()) {
            return true;
        }

        $application = $request->getAttribute(Application::class);

        if ($userPermissions->isLeadOfApplication($application)) {
            return true;
        }

        if ($permissions->canUserBuild($user, $application)) {
            return true;
        }

        return false;
    }
}
