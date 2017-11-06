<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Middleware\ACL;

use Hal\Core\Entity\User;
use Hal\UI\Security\UserAuthorizations;
use Psr\Http\Message\ServerRequestInterface;

class AdminOrSelfMiddleware extends AbstractPermissionMiddleware
{
    /**
     * @inheritDoc
     */
    protected function isAllowed(ServerRequestInterface $request, User $user, UserAuthorizations $authorizations): bool
    {
        if ($authorizations->isSuper()) {
            return true;
        }

        $resourceUser = $request->getAttribute(User::class);
        if ($user === $resourceUser) {
            return true;
        }

        return false;
    }
}
