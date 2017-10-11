<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Middleware\ACL;

use Hal\Core\Entity\Application;
use Hal\Core\Entity\Organization;
use Hal\Core\Entity\User;
use Hal\UI\Security\UserAuthorizations;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Note: Supers also pass this middleware bouncer.
 */
class OwnerMiddleware extends AbstractPermissionMiddleware
{
    /**
     * @inheritDoc
     */
    protected function isAllowed(ServerRequestInterface $request, User $user, UserAuthorizations $authorizations): bool
    {
        // Allow if super
        if ($authorizations->isSuper()) {
            return true;
        }

        // Allow if owner (app)
        $application = $request->getAttribute(Application::class);
        if ($authorizations->isOwnerOf($application)) {
            return true;
        }

        // Allow if owner (org)
        $organization = $request->getAttribute(Organization::class);
        if ($authorizations->isOwnerOf($organization)) {
            return true;
        }

        return false;
    }
}
