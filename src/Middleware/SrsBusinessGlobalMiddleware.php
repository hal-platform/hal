<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Middleware;

use Hal\UI\Controllers\TemplatedControllerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\HTTP\CookieHandler;
use QL\Panthor\MiddlewareInterface;

/**
 * Loads the session from a cookie and populates into:
 * - Request (attribute: serious_business)
 * - Template Context (variable: serious_business)
 */
class SrsBusinessGlobalMiddleware implements MiddlewareInterface
{
    use TemplatedControllerTrait;

    const COOKIE_NAME = 'seriousbusiness';
    const TEMPLATE_ATTRIBUTE = 'is_serious_business_mode';

    /**
     * @var CookieHandler
     */
    private $cookies;

    /**
     * @param CookieHandler $cookies
     */
    public function __construct(CookieHandler $cookies)
    {
        $this->cookies = $cookies;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $value = $this->cookies->getCookie($request, self::COOKIE_NAME);

        $seriousBusiness = ($value === '1');

        // attach to request
        $request = $this
            ->withContext($request, [self::TEMPLATE_ATTRIBUTE => $seriousBusiness])
            ->withAttribute(self::TEMPLATE_ATTRIBUTE, $seriousBusiness);

        return $next($request, $response);
    }
}
