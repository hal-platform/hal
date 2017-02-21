<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Middleware;

use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Flash;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\HTTP\CookieHandler;
use QL\Panthor\MiddlewareInterface;

/**
 * Loads the flash from a cookie and populates into:
 * - Request (attribute: flash)
 * - Template Context (variable: flash)
 */
class FlashGlobalMiddleware implements MiddlewareInterface
{
    use TemplatedControllerTrait;

    public const FLASH_ATTRIBUTE = 'flashes';
    public const DEFAULT_LIFETIME = '+5 minutes';

    /**
     * @var CookieHandler
     */
    private $handler;

    /**
     * @var string
     */
    private $flashLifetime;

    /**
     * @param CookieHandler $handler
     * @param string $flashLifetime
     */
    public function __construct(CookieHandler $handler, $flashLifetime = self::DEFAULT_LIFETIME)
    {
        $this->handler = $handler;
        $this->flashLifetime = $flashLifetime;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        // build flash from cookie
        $flash = $this->buildFlash($request);

        // attach to request
        $request = $this
            ->withContext($request, [self::FLASH_ATTRIBUTE => $flash])
            ->withAttribute(self::FLASH_ATTRIBUTE, $flash);

        $response = $next($request, $response);

        // serialize flash
        return $this->serializeFlash($response, $flash);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return Flash
     */
    private function buildFlash(ServerRequestInterface $request): Flash
    {
        $data = $this->handler->getCookie($request, self::FLASH_ATTRIBUTE);

        if (!$flash = Flash::fromCookie($data)) {
            $flash = new Flash;
        }

        return $flash;
    }

    /**
     * @param ResponseInterface $response
     * @param Flash $flash
     *
     * @return ResponseInterface
     */
    private function serializeFlash(ResponseInterface $response, Flash $flash): ResponseInterface
    {
        if ($flash->hasChanged()) {
            $response = $this->handler->withCookie(
                $response,
                self::FLASH_ATTRIBUTE,
                Flash::toCookie($flash),
                $this->flashLifetime
            );
        }

        return $response;
    }
}
