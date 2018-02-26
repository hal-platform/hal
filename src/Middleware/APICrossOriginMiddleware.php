<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\MiddlewareInterface;

/**
 * Add CORS headers to a response
 */
class APICrossOriginMiddleware implements MiddlewareInterface
{
    const ALLOWED_METHODS = "POST, GET, OPTIONS";
    const ALLOWED_HEADERS = "Content-Type, Authorization";

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $origin = $request->getHeader('Origin');

        $response = $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Method', self::ALLOWED_METHODS)
            ->withHeader('Access-Control-Allow-Headers', self::ALLOWED_HEADERS)
            ->withHeader('Access-Control-Allow-Credentials', true)
            ->withStatus(200);

        if ($request->getMethod() === 'OPTIONS') {
            return $response;
        }

        return $next($request, $response);
    }
}
