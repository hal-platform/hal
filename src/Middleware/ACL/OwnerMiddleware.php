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
use QL\Hal\Core\Entity\Application;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\TemplateInterface;

/**
 * Note: Admins and Supers also pass this middleware bouncer.
 */
class OwnerMiddleware implements MiddlewareInterface
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
        $requireOwner = function(ServerRequestInterface $request, ResponseInterface $response) use ($next) {
            return $this->deferredMiddleware($request, $response, $next);
        };

        return ($this->signedInMiddleware)($request, $response, $requireOwner);
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
        $permissions = $this->permissions->getUserPermissions($user);

        // Allow if admin
        if ($permissions->isButtonPusher() || $permissions->isSuper()) {
            return $next($request, $response);
        }

        // Allow if owner
        $application = $request->getAttribute(Application::class);
        if ($application && $permissions->isLead()) {
            if (in_array($application, $permissions->leadApplications())) {
                return $next($request, $response);
            }
        }

        // Denied
        return $this
            ->withTemplate($request, $response, $this->template)
            ->withStatus(403);
    }
}
