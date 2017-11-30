<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Permissions;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\User;
use Hal\Core\Entity\UserPermission;
use Hal\Core\Type\UserPermissionEnum;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Flash;
use Hal\UI\Security\AuthorizationService;
use Hal\UI\Validator\PermissionsValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Utility\URI;

class RemoveAdminPermissionsController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;

    const MSG_SUCCESS = 'Removed adminstator permissions for "%s".';
    const ERR_BAD_PERMISSIONS = 'The permissions you were trying to remove do not exist';

    /**
     * @var authorizationService
     */
    private $authorizationService;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var EntityRepository
     */
    private $permissionRepository;

    /**
     * @var PermissionsValidator
     */
    private $permissionsValidator;

    /**
     * @param authorizationService $authorizationService
     * @param URI $uri
     * @param EntityManagerInterface $em
     * @param PermissionsValidator $permissionsValidator
     */
    public function __construct(
        AuthorizationService $authorizationService,
        URI $uri,
        EntityManagerInterface $em,
        PermissionsValidator $permissionsValidator
    ) {
        $this->authorizationService = $authorizationService;
        $this->uri = $uri;
        $this->em = $em;

        $this->permissionRepository = $em->getRepository(UserPermission::class);
        $this->permissionsValidator = $permissionsValidator;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $user = $request->getAttribute(User::class);

        if(!$permissionsToRemove = $this->getPermissionsToRemove($request)) {
            return $this->redirectFailure(self::ERR_BAD_PERMISSIONS, $request, $response);
        }

        $superPermissions = $this->permissionRepository->findBy(['type' => UserPermissionEnum::TYPE_SUPER]);
        $adminPermissions = $this->permissionRepository->findBy(['type' => UserPermissionEnum::TYPE_ADMIN]);

        $valid = $this->permissionsValidator->isAdminRemovalValid(
            $this->getAuthorizations($request),
            $permissionsToRemove,
            $superPermissions,
            $adminPermissions
        );

        if ($valid === true) {
            $this->em->flush();
            $this->authorizationService->clearUserCache($user);

            $this->withFlash($request, Flash::SUCCESS, sprintf(self::MSG_SUCCESS, $user->username()));

            return $this->withRedirectRoute($response, $this->uri, 'admin.permissions');
        } else {
            return $this->redirectFailure($valid, $request, $response);
        }
    }

    /**
     * @param string $reason
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    private function redirectFailure(string $reason, ServerRequestInterface $request, ResponseInterface $response)
    {

        $this->withFlash($request, Flash::ERROR, $reason);

        return $this->withRedirectRoute($response, $this->uri, 'admin.permissions');
    }
    /**
     * map id's to doctrine references
     *
     * @param ServerRequestInterface $request
     *
     * @return array|bool
     */
    private function getPermissionsToRemove(ServerRequestInterface $request)
    {
        $permissionIDs = $request->getParsedBody()['permissions'] ?? [];

        if (!count($permissionIDs)) {
            return[];
        }

        $permissionsToRemove = $this->permissionRepository->findBy(['id' => $permissionIDs]);

        if (count($permissionIDs) !== count($permissionsToRemove)) {
            return false;
        }

        return $permissionsToRemove;
    }
}
