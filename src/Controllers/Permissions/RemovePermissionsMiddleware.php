<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Permissions;

use Doctrine\ORM\EntityManagerInterface;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Flash;
use Hal\UI\Service\PermissionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\User;
use QL\Hal\Core\Entity\UserType;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\URI;

/**
 * Super:
 *     Add any.
 *     Remove any.
 *         - If removing super, must be at least one super left.
 *
 * ButtonPusher:
 *     Add Lead, ButtonPusher
 *     Remove Lead, ButtonPusher
 *         - If removing ButtonPusher, must be at least one ButtonPusher left.
 *
 */
class RemovePermissionsMiddleware implements MiddlewareInterface
{
    use RedirectableControllerTrait;
    use RemovalPermissionsTrait;
    use SessionTrait;
    use TemplatedControllerTrait;

    private const ERR_DENIED = 'Access Denied';

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var PermissionService
     */
    private $permissions;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @param EntityManagerInterface $em
     * @param TemplateInterface $template
     * @param PermissionService $permissions
     * @param URI $uri
     */
    public function __construct(
        EntityManagerInterface $em,
        TemplateInterface $template,
        PermissionService $permissions,
        URI $uri
    ) {
        $this->template = $template;
        $this->permissions = $permissions;
        $this->uri = $uri;

        $this->setEntityManagerForRemovalPermissions($em);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $currentUser = $this->getUser($request);
        $currentUserPerms = $this->permissions->getUserPermissions($currentUser);

        $user = $request->getAttribute(User::class);
        $userType = $request->getAttribute(UserType::class);

        if (!$this->isRemovalAllowed($currentUserPerms, $userType)) {
            $this->withFlash($request, Flash::ERROR, self::ERR_DENIED, $this->getRemovalDeniedReason());
            return $this->withRedirectRoute($response, $this->uri, 'admin.permissions');
        }

        if ($request->getMethod() !== 'GET') {
            return $next($request, $response);
        }

        $selectedUserPerms = $this->permissions->getUserPermissions($user);
        $appPerm = $this->permissions->getApplications($selectedUserPerms);

        return $this->withTemplate($request, $response, $this->template, [
            'user' => $user,
            'user_type' => $userType,
            'user_permissions' => $selectedUserPerms,

            'lead_applications' => $appPerm['lead'],
            'prod_applications' => $appPerm['prod'],
            'non_prod_applications' => $appPerm['non_prod'],
        ]);
    }
}
