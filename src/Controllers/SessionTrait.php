<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers;

use Hal\UI\SessionInterface;
use Hal\UI\Middleware\ACL\SignedInMiddleware;
use Hal\UI\Middleware\SessionGlobalMiddleware;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\User;

trait SessionTrait
{
    /**
     * @param ServerRequestInterface $request
     *
     * @return SessionInterface
     */
    private function getSession(ServerRequestInterface $request): SessionInterface
    {
        return $request->getAttribute(SessionGlobalMiddleware::SESSION_ATTRIBUTE);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return User|null
     */
    private function getUser(ServerRequestInterface $request): ?User
    {
        return $request->getAttribute(SignedInMiddleware::USER_ATTRIBUTE);
    }
}
