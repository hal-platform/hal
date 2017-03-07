<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Middleware\ACL;

use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Service\PermissionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\User;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\TemplateInterface;

abstract class AbstractPermissionMiddleware implements MiddlewareInterface
{
    use TemplatedControllerTrait;
    use SessionTrait;

    /**
     * @var SignedInMiddleware
     */
    private $signedInMiddleware;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var PermissionService
     */
    private $permissions;

    /**
     * @param SignedInMiddleware $signedInMiddleware
     * @param TemplateInterface $template
     * @param PermissionService $permissions
     */
    public function __construct(
        SignedInMiddleware $signedInMiddleware,
        TemplateInterface $template,
        PermissionService $permissions
    ) {
        $this->signedInMiddleware = $signedInMiddleware;

        $this->template = $template;
        $this->permissions = $permissions;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $requireAdmin = function(ServerRequestInterface $request, ResponseInterface $response) use ($next) {
            return $this->deferredMiddleware($request, $response, $next);
        };

        return ($this->signedInMiddleware)($request, $response, $requireAdmin);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     *
     * @return ResponseInterface
     */
    public function deferredMiddleware(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $user = $this->getUser($request);

        if ($this->isAllowed($request, $this->permissions, $user)) {
            return $next($request, $response);
        }

        // Denied
        return $this
            ->withTemplate($request, $response, $this->template)
            ->withStatus(403);
    }

    /**
     * @param ServerRequestInterface $request
     * @param PermissionService $permissions
     * @param User $user
     *
     * @return bool
     */
    abstract protected function isAllowed(ServerRequestInterface $request, PermissionService $permissions, User $user): bool;
}
