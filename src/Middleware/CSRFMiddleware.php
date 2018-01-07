<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Middleware;

use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Security\CSRFManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\MiddlewareInterface;
use Slim\Route;

class CSRFMiddleware implements MiddlewareInterface
{
    use TemplatedControllerTrait;

    public const CSRF_ERROR_ATTRIBUTE = 'is_csrf_error';

    private const CSRF_INPUT_NAME = '_csrf_authenticity';

    /**
     * @var CSRFManager
     */
    private $csrf;

    /**
     * @param CSRFManager $csrf
     */
    public function __construct(CSRFManager $csrf)
    {
        $this->csrf = $csrf;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if ($request->getMethod() !== 'POST') {
            return $next($request, $response);
        }

        $token = $request->getParsedBody()[self::CSRF_INPUT_NAME] ?? '';
        $form = $this->getRouteName($request);

        $isValid = $this->csrf->isTokenValid($token, $form);

        if (!$isValid) {
            $request = $this
                ->withContext($request, [self::CSRF_ERROR_ATTRIBUTE => true])
                ->withAttribute(self::CSRF_ERROR_ATTRIBUTE, true);
        }

        return $next($request, $response);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    private function getRouteName(ServerRequestInterface $request)
    {
        $route = $request->getAttribute('route');
        if ($route instanceof Route) {
            return $route->getName();
        }

        return 'unknown';
    }
}
