<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Permissions;

use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Service\PermissionService;
use Hal\UI\Service\UserPerm;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\User;
use QL\Hal\Core\Entity\UserPermission;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\TemplateInterface;

class RemoveDeploymentPermissionsMiddleware implements MiddlewareInterface
{
    use TemplatedControllerTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var PermissionService
     */
    private $permissions;

    /**
     * @param TemplateInterface $template
     * @param PermissionService $permissions
     */
    public function __construct(TemplateInterface $template, PermissionService $permissions)
    {
        $this->template = $template;
        $this->permissions = $permissions;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if ($request->getMethod() !== 'GET') {
            return $next($request, $response);
        }

        $user = $request->getAttribute(User::class);
        $userPermission = $request->getAttribute(UserPermission::class);

        $selectedUserPerms = $this->permissions->getUserPermissions($user);
        $appPerm = $this->permissions->getApplications($selectedUserPerms);

        return $this->withTemplate($request, $response, $this->template, [
            'user' => $user,
            'user_permission' => $userPermission,
            'user_permissions' => $selectedUserPerms,

            'lead_applications' => $appPerm['lead'],
            'prod_applications' => $appPerm['prod'],
            'non_prod_applications' => $appPerm['non_prod'],
        ]);
    }
}
