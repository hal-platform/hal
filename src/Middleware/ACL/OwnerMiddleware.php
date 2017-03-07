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
 * Note: Admins and Supers also pass this middleware bouncer.
 */
class OwnerMiddleware implements MiddlewareInterface
{
    /**
     * @inheritDoc
     */
    protected function isAllowed(ServerRequestInterface $request, PermissionService $permissions, User $user): bool
    {
        $permissions = $permissions->getUserPermissions($user);

        // Allow if admin
        if ($permissions->isButtonPusher() || $permissions->isSuper()) {
            return true;
        }

        // Allow if owner
        $application = $request->getAttribute(Application::class);
        if ($application && $permissions->isLead()) {
            if (in_array($application, $permissions->leadApplications())) {
                return true;
            }
        }

        return false;
    }
}
