<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Application;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Type\UserPermissionEnum;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Flash;
use Hal\UI\Security\AuthorizationService;
use Hal\UI\Security\UserAuthorizations;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Hal\Core\Entity\Application;
use Hal\Core\Entity\User;
use Hal\Core\Entity\UserPermission;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\URI;

class AddApplicationPermissionController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use TemplatedControllerTrait;
    use SessionTrait;

    const MSG_SUCCESS = 'Permissions succesfully granted!';

    const ERR_INVALID_TYPE = 'Please select a valid permission type.';
    const ERR_SELECT_A_USER = 'Please select at least one user.';

    const ERR_USER_NOT_FOUND = 'User "%s" not found in database. Users must sign-in to Hal before permissions can be granted.';
    const ERR_DUPE_PERM = 'User "%s" already has this permission.';

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
     * @var callable
     */
    private $random;

    /**
     * @var EntityRepository
     */
    private $userRepo;
    private $permissionRepo;

    /**
     * @var array
     */
    private $errors;

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
        $user = $this->getUser($request);

        $currentAuthorizations = $this->getAuthorizations($request);

        $form = [];

        if ($request->getMethod() === 'POST') {

            $form = [
                'users' => $request->getParsedBody()['users'] ?? [],
                'type' => $request->getParsedBody()['type'] ?? ''
            ];

            if ($permissions = $this->handleForm($form, $application, $currentAuthorizations)) {
                $this->withFlash($request, Flash::SUCCESS, self::MSG_SUCCESS);
                return $this->withRedirectRoute($response, $this->uri, 'application', ['application' => $application->id()]);
            }
        }

        // @todo combine this with the main add permission handler

        return $this->withTemplate($request, $response, $this->template, [
            'application' => $application,
            'availableTypes' => [
                UserPermissionEnum::TYPE_MEMBER,
                UserPermissionEnum::TYPE_OWNER
            ],
            'users' => $this->userRepo->findBy(['isDisabled' => false], ['username' => 'ASC']),

            'form' => $form,
            'errors' => $this->errors
        ]);
    }

    /**
     * This will dedupe and lowercase all usernames.
     *
     * @param mixed $users
     *
     * @return array
     */
    private function parseSubmittedUsers($users)
    {
        if (!is_array($users)) {
            $users = [];
        }

        $u = [];

        foreach ($users as $username) {
            if (is_string($username) && strlen($username) > 0) {
                $u[strtolower($username)] = $username;
            }
        }

        return array_keys($u);
    }

    /**
     * @param array $data
     * @param Application $application
     * @param UserAuthorizations $currentAuthorizations
     *
     * @return array|null
     */
    private function handleForm(array $data, Application $application, UserAuthorizations $currentAuthorizations)
    {
        $isAdmin = ($currentAuthorizations->isSuper() || $currentAuthorizations->isAdmin());

        $users = $this->parseSubmittedUsers($data['users']);
        $type = $data['type'];

        if (!$users) {
            $this->errors[] = self::ERR_SELECT_A_USER;
        }

        if (!in_array($type, [UserPermissionEnum::TYPE_MEMBER, UserPermissionEnum::TYPE_OWNER])) {
            $this->errors[] = self::ERR_INVALID_TYPE;
        }

        if ($this->errors) return null;

        // Verify users /  database lookup
        $verified = $this->validateUsers($users);

        if ($this->errors) return null;

        // verify no dupe permissions
        $this->validateDuplicatePermissions($verified, $application, $type);

        if ($this->errors) return null;

        // save
        $perms = [];
        foreach ($verified as $user) {
            $perms[] = $this->savePermissions($application, $user, $type);
        }

        $this->em->flush();

        return $perms;
    }

    /**
     * @param array $users
     *
     * @return array|null
     */
    private function validateUsers(array $users)
    {
        // Note: this is case insensitive
        $criteria = (new Criteria)->where(Criteria::expr()->in('username', $users));
        $verifiedUsers = $this->userRepo
            ->matching($criteria)
            ->toArray();

        $verifiedUsernames = array_map(function($u) {
            return strtolower($u->username());
        }, $verifiedUsers);

        foreach ($users as $u) {
            if (!in_array($u, $verifiedUsernames)) {
                $this->errors[] = sprintf(self::ERR_USER_NOT_FOUND, $u);
            }
        }

        if ($this->errors) return null;

        if (!$verifiedUsernames) {
            $this->errors[] = self::ERR_SELECT_A_USER;
        }

        if ($this->errors) return null;

        return $verifiedUsers;
    }

    /**
     * @param array $users
     * @param Application $application
     * @param string $type
     *
     * @return void
     */
    private function validateDuplicatePermissions(array $users, Application $application, $type)
    {
        $dupePermissions = $this->matching($application, $users, $type);

        foreach ($dupePermissions as $permission) {
            $this->errors[] = sprintf(self::ERR_DUPE_PERM, $permission->user()->username());
        }
    }

    /**
     * @param Application $application
     * @param User $user
     *
     * @return UserPermission
     */
    private function savePermissions(Application $application, User $user, $type)
    {
        $permissions = (new UserPermission)
            ->withApplication($application)
            ->withUser($user)
            ->withType($type);

        // Clear cache
        $this->authorizationService->addUserPermissions($permissions, true);

        return $permissions;
    }

    /**
     * @param Application $application
     * @param array $users
     * @param string $permissionType
     *
     * @return array
     */
    private function matching(Application $application, array $users, $permissionType)
    {
        $criteria = (new Criteria)
            ->where(Criteria::expr()->in('user', $users))
            ->andWhere(Criteria::expr()->eq('application', $application))
            ->andWhere(Criteria::expr()->eq('type', $permissionType));

        return $this->permissionRepo
            ->matching($criteria)
            ->toArray();
    }
}
