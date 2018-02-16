<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Permissions;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Type\UserPermissionEnum;
use Hal\Core\Entity\Application;
use Hal\Core\Entity\Organization;
use Hal\Core\Entity\User;
use Hal\Core\Entity\User\UserPermission;
use Hal\UI\Controllers\CSRFTrait;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Security\AuthorizationService;
use Hal\UI\Security\UserAuthorizations;
use Hal\UI\Validator\ValidatorErrorTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\URI;
use QL\MCP\Common\GUID;

class AddEntityPermissionsController implements ControllerInterface
{
    use CSRFTrait;
    use RedirectableControllerTrait;
    use TemplatedControllerTrait;
    use SessionTrait;
    use ValidatorErrorTrait;

    private const MSG_SUCCESS = 'Permissions succesfully granted!';

    private const ERR_INVALID_TYPE = 'Please select a valid permission type.';
    private const ERR_SELECT_A_USER = 'Please select at least one user.';

    private const ERR_INVALID_USER = 'Unknown user specified.';
    private const ERR_AMBIGUOUS_USER = 'Ambiguous username provided. Multiple users found for username "%s".';
    private const ERR_USER_NOT_FOUND = 'User "%s" not found in database. Users must sign-in to Hal before permissions can be granted.';
    private const ERR_DUPE_PERM = 'User "%s" already has this permission.';

    private const ERR_WUT = 'Something bad has happened.';

    private const AVAILABLE_PERMISSION_TYPES = [
        UserPermissionEnum::TYPE_MEMBER,
        UserPermissionEnum::TYPE_OWNER
    ];

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var AuthorizationService
     */
    private $authorizationService;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @var EntityRepository
     */
    private $userRepo;
    private $permissionRepo;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param AuthorizationService $authorizationService
     * @param URI $uri
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        AuthorizationService $authorizationService,
        URI $uri
    ) {
        $this->template = $template;
        $this->em = $em;
        $this->authorizationService = $authorizationService;
        $this->uri = $uri;

        $this->userRepo = $em->getRepository(User::class);
        $this->permissionRepo = $em->getRepository(UserPermission::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $application = $request->getAttribute(Application::class);
        $organization = $request->getAttribute(Organization::class);

        // This controller supports both app and org permissions.
        // If neither is set we can safely assume something terrible has occurred.
        if (!($application xor $organization)) {
            // redirecting instead of 404 because of laziness
            $this->withFlashError($request, self::ERR_WUT);
            return $this->withRedirectRoute($response, $this->uri, 'home');
        }

        $entity = $application ?? $organization;
        $form = $this->getFormData($request);

        if ($permissions = $this->handleForm($form, $request, $entity)) {
            $this->withFlashSuccess($request, self::MSG_SUCCESS);

            if ($application instanceof Application) {
                $routeParams = ['application', ['application' => $entity->id()]];
            } else {
                $routeParams = ['organization', ['organization' => $entity->id()]];
            }

            return $this->withRedirectRoute($response, $this->uri, ...$routeParams);
        }

        // @todo combine this with the main add permission handler

        return $this->withTemplate($request, $response, $this->template, [
            'application' => $application,
            'organization' => $organization,

            'available_types' => self::AVAILABLE_PERMISSION_TYPES,

            'users' => $this->userRepo->findBy(['isDisabled' => false], ['name' => 'ASC']),

            'form' => $form,
            'errors' => $this->errors()
        ]);
    }

    /**
     * This will dedupe and lowercase all usernames.
     *
     * @param array $users
     *
     * @return array
     */
    private function parseSubmittedUsers(array $users)
    {
        $names = $ids = [];

        foreach ($users as $user) {
            $id = GUID::createFromHex($user);
            if ($id) {
                $ids[] = $id->format(GUID::HYPHENATED);
            } else {
                $names[] = $user;
            }
        }

        return [$ids, $names];
    }

    /**
     * @param array $data
     * @param ServerRequestInterface $request
     * @param Application|Organization $entity
     *
     * @return array|null
     */
    private function handleForm(array $data, ServerRequestInterface $request, $entity)
    {
        if ($request->getMethod() !== 'POST') {
            return null;
        }

        if (!$this->isCSRFValid($request)) {
            return null;
        }

        $type = $data['type'];
        $users = $data['users'];

        $this->validateAuthorization($type);

        if ($this->hasErrors()) {
            return null;
        }

        // Verify users /  database lookup
        $verified = $this->validateUsers($users);

        if ($this->hasErrors()) {
            return null;
        }

        // verify no dupe permissions
        $this->validateDuplicatePermissions($verified, $entity, $type);

        if ($this->hasErrors()) {
            return null;
        }

        // save
        $perms = [];
        foreach ($verified as $user) {
            $perms[] = $this->savePermissions($entity, $user, $type);
        }

        $this->em->flush();

        return $perms;
    }

    /**
     * @param string $type
     *
     * @return void
     */
    private function validateAuthorization($type)
    {
        if (!in_array($type, [UserPermissionEnum::TYPE_MEMBER, UserPermissionEnum::TYPE_OWNER])) {
            $this->addError(self::ERR_INVALID_TYPE);
        }
    }

    /**
     * @param array $users
     *
     * @return array|null
     */
    private function validateUsers(array $users)
    {
        if (!$users) {
            $this->addError(self::ERR_SELECT_A_USER);
            return null;
        }

        // separate anything that looks like a guid into IDs.
        // This allows users to add permission by username, or by picking a username in a dropdown.
        list($ids, $names) = $this->parseSubmittedUsers($users);

        $total = count($ids) + count($names);
        $verified = $this->findUsers($ids, $names);

        $verifiedNames = [];

        foreach ($verified as $user) {
            $name = $user->name();
            if (isset($verifiedNames[$name])) {
                $this->addError(sprintf(self::ERR_AMBIGUOUS_USER, $name));
                continue;
            }
            $verifiedNames[$name] = $name;
        }

        if ($this->hasErrors()) {
            return null;
        }

        if (count($verified) < $total) {
            $this->addError(self::ERR_INVALID_USER);
        }

        // only do specific checks on user names.
        foreach ($names as $u) {
            if (!isset($verifiedNames[$u])) {
                $this->addError(sprintf(self::ERR_USER_NOT_FOUND, $u));
            }
        }

        if ($this->hasErrors()) {
            return null;
        }

        return $verified;
    }

    /**
     * @param array $users
     * @param Application|Organization $entity
     * @param string $type
     *
     * @return void
     */
    private function validateDuplicatePermissions(array $users, $entity, $type)
    {
        $criteria = (new Criteria)
            ->where(Criteria::expr()->in('user', $users))
            ->andWhere(Criteria::expr()->eq('type', $type));

        if ($entity instanceof Application) {
            $criteria = $criteria
                ->andWhere(Criteria::expr()->eq('application', $entity));

        } else {
            $criteria = $criteria
                ->andWhere(Criteria::expr()->eq('organization', $entity));
        }

        $dupePermissions = $this->permissionRepo
            ->matching($criteria)
            ->toArray();

        foreach ($dupePermissions as $permission) {
            $this->addError(sprintf(self::ERR_DUPE_PERM, $permission->user()->name()));
        }
    }

    /**
     * @param Application|Organization $entity
     * @param User $user
     *
     * @return UserPermission
     */
    private function savePermissions($entity, User $user, $type)
    {
        $permissions = (new UserPermission)
            ->withUser($user)
            ->withType($type);

        if ($entity instanceof Application) {
            $permissions->withApplication($entity);
        } else {
            $permissions->withOrganization($entity);
        }

        // Clear cache
        $this->authorizationService->addUserPermissions($permissions, true);

        return $permissions;
    }

    /**
     * @param array $ids
     * @param array $names
     *
     * @return array
     */
    private function findUsers(array $ids, array $names)
    {
        // Here we search for any user ID
        // or - any matching username
        $criteria = (new Criteria)
            ->where(Criteria::expr()
                ->in('id', $ids))
            ->orWhere(Criteria::expr()
                ->in('name', $names));

        return $this->userRepo
            ->matching($criteria)
            ->toArray();
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return array
     */
    private function getFormData(ServerRequestInterface $request)
    {
        $data = $request->getParsedBody();

        $users = $data['users'] ?? [];

        if (!is_array($users)) {
            $users = [];
        }

        // Filter out empty inputs
        $users = array_filter($users, function ($v) {
            return strlen($v) !== 0;
        });

        $form = [
            'users' => $users,
            'type' => $data['type'] ?? ''
        ];

        return $form;
    }
}
