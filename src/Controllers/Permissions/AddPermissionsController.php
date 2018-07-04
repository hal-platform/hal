<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Permissions;

use Hal\Core\Entity\User;
use Hal\Core\Entity\User\UserPermission;
use Hal\Core\Type\UserPermissionEnum;
use Hal\UI\Controllers\CSRFTrait;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Security\AuthorizationHydrator;
use Hal\UI\Security\AuthorizationService;
use Hal\UI\Security\UserAuthorizations;
use Hal\UI\Validator\PermissionsValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\URI;

class AddPermissionsController implements ControllerInterface
{
    use CSRFTrait;
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;

    private const MSG_SUCCESS = 'New permissions added for "%s".';

    private const AVAILABLE_TYPES = [
        UserPermissionEnum::TYPE_ADMIN,
        UserPermissionEnum::TYPE_SUPER,
    ];

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var PermissionsValidator
     */
    private $permissionsValidator;

    /**
     * @var AuthorizationService
     */
    private $authorizationService;

    /**
     * @var AuthorizationHydrator
     */
    private $authorizationHydrator;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @param TemplateInterface $template
     * @param PermissionsValidator $permissionsValidator
     * @param AuthorizationService $authorizationService
     * @param AuthorizationHydrator $authorizationHydrator
     * @param URI $uri
     */
    public function __construct(
        TemplateInterface $template,
        PermissionsValidator $permissionsValidator,
        AuthorizationService $authorizationService,
        AuthorizationHydrator $authorizationHydrator,
        URI $uri
    ) {
        $this->template = $template;

        $this->permissionsValidator = $permissionsValidator;

        $this->authorizationService = $authorizationService;
        $this->authorizationHydrator = $authorizationHydrator;

        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $user = $request->getAttribute(User::class);

        $currentUserAuthorizations = $this->getAuthorizations($request);

        $authorizations = $this->authorizationService->getUserAuthorizations($user);
        $permissions = $this->authorizationHydrator->hydrateAuthorizations($user, $authorizations);

        $form = $this->getFormData($request);

        if ($permission = $this->handleForm($form, $request, $user, $currentUserAuthorizations, $authorizations)) {
            $this->authorizationService->addUserPermissions($permission);

            $this->withFlashSuccess($request, sprintf(self::MSG_SUCCESS, $user->name()));
            return $this->withRedirectRoute($response, $this->uri, 'admin.permissions');
        }

        return $this->withTemplate($request, $response, $this->template, [
            'form' => $form,
            'errors' => $this->permissionsValidator->errors(),

            'user' => $user,
            'user_authorizations' => $authorizations,
            'user_permissions' => $permissions,

            'available_types' => self::AVAILABLE_TYPES,
        ]);
    }

    /**
     * @param array $data
     * @param ServerRequestInterface $request
     * @param User $user
     * @param UserAuthorizations $current
     * @param UserAuthorizations $selected
     *
     * @return UserPermission|null
     */
    private function handleForm(array $data, ServerRequestInterface $request, User $user, UserAuthorizations $current, UserAuthorizations $selected)
    {
        if ($request->getMethod() !== 'POST') {
            return null;
        }

        if (!$this->isCSRFValid($request)) {
            return null;
        }

        $type = $data['permission_type'];

        return $this->permissionsValidator->isValid($type, $current, $selected, $user);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return array
     */
    private function getFormData(ServerRequestInterface $request)
    {
        $data = $request->getParsedBody();

        $form = [
            'permission_type' => $data['permission_type'] ?? '',
        ];

        return $form;
    }
}
