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
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\TemplateInterface;

/**
 * Note: Supers also pass this middleware bouncer.
 */
class AdminMiddleware implements MiddlewareInterface
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
        $requireAdmin = function (ServerRequestInterface $request, ResponseInterface $response) use ($next) {

            $user = $this->getUser($request);
            $permissions = $this->permissions->getUserPermissions($user);

            if ($permissions->isButtonPusher() || $permissions->isSuper()) {
                return $next($request, $response);
            }

            $response = $this->withTemplate(
                $request,
                $response,
                $this->template,
                []
            );

            return $response->withStatus(403);
        };

        return call_user_func($this->signedInMiddleware, $request, $response, $requireAdmin);
    }
}
