<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Middleware;

use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Session;
use Hal\UI\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\HTTP\CookieHandler;
use QL\Panthor\MiddlewareInterface;

/**
 * Loads the session from a cookie and populates into:
 * - Request (attribute: session)
 * - Template Context (variable: session)
 */
class SessionGlobalMiddleware implements MiddlewareInterface
{
    use TemplatedControllerTrait;

    public const SESSION_ATTRIBUTE = 'session';
    public const DEFAULT_LIFETIME = '+20 minutes';

    /**
     * @var CookieHandler
     */
    private $handler;

    /**
     * @var string
     */
    private $sessionLifetime;

    /**
     * @param CookieHandler $handler
     * @param string $sessionLifetime
     */
    public function __construct(CookieHandler $handler, $sessionLifetime = self::DEFAULT_LIFETIME)
    {
        $this->handler = $handler;
        $this->sessionLifetime = $sessionLifetime;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        // build session
        $session = $this->buildSession($request);

        $request = $this
            ->withContext($request, [self::SESSION_ATTRIBUTE => $session])
            ->withAttribute(self::SESSION_ATTRIBUTE, $session);

        // attach to request
        $response = $next($request, $response);

        // render session
        return $this->serializeSession($response, $session);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return SessionInterface
     */
    private function buildSession(ServerRequestInterface $request): SessionInterface
    {
        $data = $this->handler->getCookie($request, self::SESSION_ATTRIBUTE);

        if (!$session = Session::fromCookie($data)) {
            $session = new Session;
        }

        return $session;
    }

    /**
     * @param ResponseInterface $response
     * @param SessionInterface $session
     *
     * @return ResponseInterface
     */
    private function serializeSession(ResponseInterface $response, SessionInterface $session): ResponseInterface
    {
        if ($session->hasChanged()) {
            $response = $this->handler->withCookie(
                $response,
                self::SESSION_ATTRIBUTE,
                Session::toCookie($session),
                $this->sessionLifetime
            );
        }

        return $response;
    }
}
