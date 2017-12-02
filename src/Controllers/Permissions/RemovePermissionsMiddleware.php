<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Permissions;

use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\Entity\UserPermission;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Flash;
use Hal\UI\Middleware\UserSessionGlobalMiddleware;
use Hal\UI\Security\AuthorizationHydrator;
use Hal\UI\Security\AuthorizationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Hal\Core\Entity\User;
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
     * @var URI
     */
    private $uri;

    /**
     * @var AuthorizationService
     */
    private $authorizationService;

    /**
     * @var AuthorizationHydrator
     */
    private $authorizationHydrator;

    /**
     * @param EntityManagerInterface $em
     * @param TemplateInterface $template
     * @param URI $uri
     * @param AuthorizationService $authorizationService
     * @param AuthorizationHydrator $authorizationHydrator
     */
    public function __construct(
        EntityManagerInterface $em,
        TemplateInterface $template,
        URI $uri,
        AuthorizationService $authorizationService,
        AuthorizationHydrator $authorizationHydrator
    ) {
        $this->template = $template;
        $this->uri = $uri;

        $this->authorizationService = $authorizationService;
        $this->authorizationHydrator = $authorizationHydrator;

        $this->setEntityManagerForRemovalPermissions($em);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $currentUserAuthorizations = $request->getAttribute(UserSessionGlobalMiddleware::AUTHORIZATIONS_ATTRIBUTE);

        $user = $request->getAttribute(User::class);
        $permission = $request->getAttribute(UserPermission::class);

        if (!$this->isRemovalAllowed($currentUserAuthorizations, $permission)) {
            $this->withFlash($request, Flash::ERROR, self::ERR_DENIED, $this->getRemovalDeniedReason());
            return $this->withRedirectRoute($response, $this->uri, 'admin.permissions');
        }

        if ($request->getMethod() !== 'GET') {
            return $next($request, $response);
        }

        $selectedUserAuthorizations = $this->authorizationService->getUserAuthorizations($user);
        $selectedUserPerms = $this->authorizationHydrator->hydrateAuthorizations($user, $selectedUserAuthorizations);

        return $this->withTemplate($request, $response, $this->template, [
            'user' => $user,
            'userAuthorizations' => $selectedUserAuthorizations,
            'userPermissions' => $selectedUserPerms,

            'permission' => $permission
        ]);
    }
}
