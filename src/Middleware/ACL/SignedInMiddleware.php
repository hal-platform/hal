<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Middleware\ACL;

use Hal\Core\Entity\User;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\Utility\URI;

/**
 * Verifies the user is signed in.
 */
class SignedInMiddleware implements MiddlewareInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;

    private const ROUTE_SIGNIN = 'signin';

    /**
     * @var URI
     */
    private $uri;

    /**
     * @param URI $uri
     */
    public function __construct(URI $uri)
    {
        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $user = $this->getUser($request);

        if ($user instanceof User) {
            return $next($request, $response);
        }

        return $this->withRedirectRoute($response, $this->uri, self::ROUTE_SIGNIN);
    }
}
