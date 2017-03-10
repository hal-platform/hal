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
use QL\Hal\Core\Entity\DeploymentView;
use QL\Hal\Core\Entity\User;

/**
 * Note: Admins and Supers also pass this middleware bouncer.
 */
class ManageViewMiddleware extends AbstractPermissionMiddleware
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

        $view = $request->getAttribute(DeploymentView::class);

        if ($userPermissions->isLeadOfApplication($view->application())) {
            return true;
        }

        // Users with build permissions can manage, if the view is shared (has no owner)
        if (!$view->user() && $permissions->canUserBuild($user, $view->application())) {
            return true;
        }

        return false;
    }
}
