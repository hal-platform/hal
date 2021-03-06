<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Middleware;

use Hal\Core\Entity\Application;
use Hal\Core\Entity\EncryptedProperty;
use Hal\Core\Entity\Target;
use Hal\Core\Entity\User;
use Hal\Core\Entity\User\UserPermission;
use Hal\Core\Entity\User\UserToken;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\MiddlewareInterface;

/**
 * Ensure when resources are nested in URL structure, that they belong to each other.
 *
 * Example:
 *
 * /application/$id/target/$target
 * $id and $target are both unique, so this ensures the $target's application matches
 * the application.
 *
 * 404 (Not Found) is returned if a mismatch is found.
 */
class NestedEntityMiddleware implements MiddlewareInterface
{
    /**
     * @var callable
     */
    private $notFound;

    /**
     * @param callable $notFound
     */
    public function __construct(callable $notFound)
    {
        $this->notFound = $notFound;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        // $target->application() - $application
        if ($target = $request->getAttribute(Target::class)) {
            $application = $request->getAttribute(Application::class);

            if ($application !== $target->application()) {
                return ($this->notFound)($request, $response);
            }
        }

        // $encrypted->application() - $application
        if ($encrypted = $request->getAttribute(EncryptedProperty::class)) {
            $application = $request->getAttribute(Application::class);

            if ($application !== $encrypted->application()) {
                return ($this->notFound)($request, $response);
            }
        }

        // $token->user() - $user
        if ($token = $request->getAttribute(UserToken::class)) {
            $user = $request->getAttribute(User::class);

            if ($user !== $token->user()) {
                return ($this->notFound)($request, $response);
            }
        }

        // $userPermission->user() - $user
        if ($userPermission = $request->getAttribute(UserPermission::class)) {
            $user = $request->getAttribute(User::class);

            if ($user !== $userPermission->user()) {
                return ($this->notFound)($request, $response);
            }
        }

        return $next($request, $response);
    }
}
