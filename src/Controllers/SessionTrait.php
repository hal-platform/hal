<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers;

use Hal\Core\Entity\User;
use Hal\UI\Flash;
use Hal\UI\SessionInterface;
use Hal\UI\Middleware\FlashGlobalMiddleware;
use Hal\UI\Middleware\SessionGlobalMiddleware;
use Hal\UI\Middleware\UserSessionGlobalMiddleware;
use Hal\UI\Security\UserAuthorizations;
use Psr\Http\Message\ServerRequestInterface;

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
     * @return Flash
     */
    private function getFlash(ServerRequestInterface $request): Flash
    {
        return $request->getAttribute(FlashGlobalMiddleware::FLASH_ATTRIBUTE);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return User|null
     */
    private function getUser(ServerRequestInterface $request): ?User
    {
        return $request->getAttribute(UserSessionGlobalMiddleware::USER_ATTRIBUTE);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return UserAuthorizations|null
     */
    private function getAuthorizations(ServerRequestInterface $request): ?UserAuthorizations
    {
        return $request->getAttribute(UserSessionGlobalMiddleware::AUTHORIZATIONS_ATTRIBUTE);
    }

    /**
     * @param ServerRequestInterface $request
     * @param string $type
     * @param string $message
     * @param string $details
     *
     * @return ServerRequestInterface
     */
    private function withFlash(
        ServerRequestInterface $request,
        string $type,
        string $message,
        string $details = ''
    ): ServerRequestInterface {
        $flash = $this
            ->getFlash($request)
            ->withMessage($type, $message, $details);

        return $request;
    }
}
