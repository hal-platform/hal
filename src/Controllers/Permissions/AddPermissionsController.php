<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Permissions;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\Application;
use Hal\Core\Entity\Organization;
use Hal\Core\Entity\User;
use Hal\Core\Entity\UserPermission;
use Hal\Core\Type\UserPermissionEnum;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Flash;
use Hal\UI\Middleware\UserSessionGlobalMiddleware;
use Hal\UI\Security\AuthorizationHydrator;
use Hal\UI\Security\AuthorizationService;
use Hal\UI\Security\UserAuthorizations;
use Hal\UI\Validator\PermissionsValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\MCP\Cache\CachingTrait;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\JSON;
use QL\Panthor\Utility\URI;

class AddPermissionsController implements ControllerInterface
{
    use CachingTrait;
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;

    const CACHE_KEY_PERMISSION_APPLICATIONS = 'page:db.applications';
    const CACHE_KEY_PERMISSION_ORGANIZATIONS = 'page:db.organizations';

    const MSG_SUCCESS = 'New permissions added for "%s".';

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var EntityRepository
     */
    private $applicationRepository;
    private $organizationRepository;

    /**
     * @var JSON
     */
    private $json;

    /**
     * @var URI
     */
    private $uri;

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
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param JSON $json
     * @param URI $uri
     * @param PermissionsValidator $permissionsValidator
     * @param AuthorizationService $authorizationService
     * @param AuthorizationHydrator $authorizationHydrator
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        JSON $json,
        URI $uri,
        PermissionsValidator $permissionsValidator,
        AuthorizationService $authorizationService,
        AuthorizationHydrator $authorizationHydrator
    ) {
        $this->template = $template;

        $this->json = $json;
        $this->uri = $uri;

        $this->permissionsValidator = $permissionsValidator;
        $this->authorizationService = $authorizationService;

        $this->em = $em;
        $this->applicationRepository = $em->getRepository(Application::class);
        $this->organizationRepository = $em->getRepository(Organization::class);

        $this->errors = [];
        $this->authorizationHydrator = $authorizationHydrator;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $selectedUser = $request->getAttribute(User::class);

        $currentUserAuthorizations = $this->getAuthorizations($request);
        $selectedUserAuthorizations = $this->authorizationService->getUserAuthorizations($selectedUser);

        $form = $this->getFormData($request);

        if ($permission = $this->handleForm($form, $request, $selectedUser, $currentUserAuthorizations, $selectedUserAuthorizations)) {
            $this->authorizationService->addUserPermissions($permission);

            $this->withFlash($request, Flash::SUCCESS, sprintf(self::MSG_SUCCESS, $permission->user()->username()));

            return $this->withRedirectRoute($response, $this->uri, 'admin.permissions');
        }

        $availableApplications = $this->getAvailableApplications();
        $availableOrganizations = $this->getAvailableOrganizations();
        $ownerApplications = [];

        foreach ($availableApplications as $application) {
            if ($selectedUserAuthorizations->isOwnerOf($application)) {
                $ownerApplications[] = $application;
            }
        }

        return $this->withTemplate($request, $response, $this->template, [
            'form' => $form,
            'errors' => $this->permissionsValidator->errors(),

            'user' => $selectedUser,
            'userAuthorizations' => $selectedUserAuthorizations,
            'leadApplications' => $ownerApplications,

            'availableTypes' => UserPermissionEnum::options(),
            'availableApplications' => $availableApplications,
            'availableOrganizations' => $availableOrganizations,

            'permissions' => $this->authorizationHydrator->hydrateAuthorizations($selectedUser, $selectedUserAuthorizations)
        ]);
    }


    /**
     * @param ServerRequestInterface $request
     *
     * @return array
     */
    private function getFormData(ServerRequestInterface $request)
    {
        if ($request->getMethod() === 'POST') {
            $form = [
                'type' => $request->getParsedBody()['type'] ?? '',
                'application' => $request->getParsedBody()['application'] ?? '',
                'organization' => $request->getParsedBody()['organization'] ?? ''
            ];
        } else {
            $form = [
                'type' => '',
                'application' => '',
                'organization' => ''
            ];
        }

        return $form;
    }

    /**
     * @param array $data
     * @param ServerRequestInterface $request
     * @param UserAuthorizations $currentAuthorizations
     * @param UserAuthorizations $selectedPerm
     *
     * @return UserPermission|null
     * @internal param UserPermission $currentPerm
     */
    private function handleForm(array $data, ServerRequestInterface $request, User $user, UserAuthorizations $currentAuthorizations, UserAuthorizations $selectedPerm)
    {
        if ($request->getMethod() !== 'POST') {
            return null;
        }

        $type = $data['type'];
        $appID = $data['application'];
        $orgID = $data['organization'];

        return $this->permissionsValidator->isValid($type, $currentAuthorizations, $selectedPerm, $user, $appID, $orgID);
    }

    /**
     * @return array
     */
    private function getAvailableApplications()
    {
        // external cache
        if ($result = $this->getFromCache(self::CACHE_KEY_PERMISSION_APPLICATIONS)) {
            $decoded = $this->json->decode($result);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        $applications = $this->applicationRepository->findBy([], ['name' => 'ASC']);

        $data = $noOrg = [];
        foreach ($applications as $app) {
            if (!$org = $app->organization()) {
                $noOrg[$app->id()] = $app->name();
                continue;
            }

            if (!isset($data[$org->name()])) {
                $data[$org->name()] = [];
            }

            $data[$org->name()][$app->id()] = $app->name();
        }

        if ($noOrg) {
            $data['X'] = $noOrg;
        }

        uksort($data, function ($a, $b) {
            return strcasecmp($a, $b);
        });

        $this->setToCache(self::CACHE_KEY_PERMISSION_APPLICATIONS, $this->json->encode($data));

        return $data;
    }

    /**
     * @return array
     */
    private function getAvailableOrganizations()
    {
        // external cache
        if ($result = $this->getFromCache(self::CACHE_KEY_PERMISSION_ORGANIZATIONS)) {
            $decoded = $this->json->decode($result);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        $organizations = $this->organizationRepository->findBy([], ['name' => 'ASC']);

        $data = [];
        foreach ($organizations as $organization) {
            $data[$organization->id()] = $organization->name();
        }

        uksort($data, function ($a, $b) {
            return strcasecmp($a, $b);
        });

        $this->setToCache(self::CACHE_KEY_PERMISSION_ORGANIZATIONS, $this->json->encode($data));

        return $data;
    }
}
