<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Permissions;

use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\Entity\User;
use Hal\Core\Entity\User\UserPermission;
use Hal\UI\Controllers\CSRFTrait;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Security\AuthorizationHydrator;
use Hal\UI\Security\AuthorizationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\URI;

class RemovePermissionsController implements ControllerInterface
{
    use CSRFTrait;
    use RedirectableControllerTrait;
    use RemovePermissionsTrait;
    use SessionTrait;
    use TemplatedControllerTrait;

    private const MSG_SUCCESS = 'User Permission "%s" revoked from "%s".';
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
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param AuthorizationService $authorizationService
     * @param AuthorizationHydrator $authorizationHydrator
     * @param URI $uri
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        AuthorizationService $authorizationService,
        AuthorizationHydrator $authorizationHydrator,
        URI $uri
    ) {
        $this->template = $template;

        $this->authorizationService = $authorizationService;
        $this->authorizationHydrator = $authorizationHydrator;

        $this->uri = $uri;

        $this->setEntityManagerForRemovalPermissions($em);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $currentUserAuthorizations = $this->getAuthorizations($request);

        $user = $request->getAttribute(User::class);
        $permission = $request->getAttribute(UserPermission::class);

        if (!$this->isRemovalAllowed($currentUserAuthorizations, $permission)) {
            $this->withFlashError($request, self::ERR_DENIED, $this->getRemovalDeniedReason());
            return $this->withRedirectRoute($response, $this->uri, 'admin.permissions');
        }

        if ($this->handleForm($request, $permission)) {
            $type = $permission->type();
            $name = $permission->user()->name();

            $this->withFlashSuccess($request, sprintf(self::MSG_SUCCESS, $type, $name));
            return $this->withRedirectRoute($response, $this->uri, 'admin.permissions');
        }

        $selectedUserAuthorizations = $this->authorizationService->getUserAuthorizations($user);
        $selectedUserPerms = $this->authorizationHydrator->hydrateAuthorizations($user, $selectedUserAuthorizations);

        return $this->withTemplate($request, $response, $this->template, [
            'user' => $user,
            'user_authorizations' => $selectedUserAuthorizations,
            'user_permissions' => $selectedUserPerms,

            'permission' => $permission
        ]);
    }

    /**
     * @param ServerRequestInterface $request
     * @param UserPermission $permission
     *
     * @return bool
     */
    private function handleForm(ServerRequestInterface $request, UserPermission $permission)
    {
        if ($request->getMethod() !== 'POST') {
            return false;
        }

        if (!$this->isCSRFValid($request)) {
            return false;
        }

        $this->authorizationService->removeUserPermissions($permission);
        return true;
    }
}
