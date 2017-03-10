<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\DeploymentView;
use QL\Hal\Core\Entity\EncryptedProperty;
use QL\Hal\Core\Entity\Token;
use QL\Hal\Core\Entity\User;
use QL\Hal\Core\Entity\UserType;
use QL\Hal\Core\Entity\UserPermission;
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
        if ($target = $request->getAttribute(Deployment::class)) {
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
        if ($token = $request->getAttribute(Token::class)) {
            $user = $request->getAttribute(User::class);

            if ($user !== $token->user()) {
                return ($this->notFound)($request, $response);
            }
        }

        // $userType->user() - $user
        if ($userType = $request->getAttribute(UserType::class)) {
            $user = $request->getAttribute(User::class);

            if ($user !== $userType->user()) {
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

        // $view->application() - $application
        if ($view = $request->getAttribute(DeploymentView::class)) {
            $application = $request->getAttribute(Application::class);

            if ($application !== $view->application()) {
                return ($this->notFound)($request, $response);
            }
        }

        // $pool->view() - $view
        if ($pool = $request->getAttribute(DeploymentPool::class)) {
            $view = $request->getAttribute(DeploymentView::class);

            if ($view !== $pool->view()) {
                return ($this->notFound)($request, $response);
            }
        }

        return $next($request, $response);
    }
}
