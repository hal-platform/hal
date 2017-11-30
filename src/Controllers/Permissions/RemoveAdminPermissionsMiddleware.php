<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Permissions;

use Hal\Core\Entity\User;
use Hal\Core\Type\UserPermissionEnum;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Security\AuthorizationHydrator;
use Hal\UI\Security\AuthorizationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\TemplateInterface;

class RemoveAdminPermissionsMiddleware implements MiddlewareInterface
{
    use TemplatedControllerTrait;
    use RemovalPermissionsTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var AuthorizationService
     */
    private $authorizationService;

    /**
     * @var AuthorizationHydrator
     */
    private $authorizationHydrator;

    /**
     * @param TemplateInterface $template
     * @param AuthorizationService $authorizationService
     * @param AuthorizationHydrator $authorizationHydrator
     */
    public function __construct(
        TemplateInterface $template,
        AuthorizationService $authorizationService,
        AuthorizationHydrator $authorizationHydrator
    ) {
        $this->template = $template;
        $this->authorizationService = $authorizationService;
        $this->authorizationHydrator = $authorizationHydrator;
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

        $selectedUserAuthorizations = $this->authorizationService->getUserAuthorizations($user);
        $permissions = $this->authorizationHydrator->hydrateAuthorizations($user, $selectedUserAuthorizations);

        $adminPermissions = array_merge($permissions['admin'], $permissions['super']);

        return $this->withTemplate($request, $response, $this->template, [
            'user' => $user,
            'userAuthorizations' => $selectedUserAuthorizations,
            'permissions' => $permissions,

            'adminPermissions' => $adminPermissions
        ]);
    }
}
