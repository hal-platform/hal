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
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Flash;
use Hal\UI\Service\PermissionService;
use Hal\UI\Service\UserPerm;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\User;
use QL\Hal\Core\Entity\UserPermission;
use QL\Hal\Core\Entity\UserType;
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
    const ERR_CANNOT_ADD_LEAD = 'Nice try, but you are not allowed to add owner permissions.';
    const ERR_CANNOT_ADD_PROD = 'Nice try, but you are not allowed to add prod permissions.';

    const ERR_USER_NOT_FOUND = 'User "%s" not found in database. Users must sign-in to Hal before permissions can be granted.';
    const ERR_DUPE_PERM = 'User "%s" already has permissions.';

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var PermissionService
     */
    private $permissions;

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
    private $typeRepo;

    /**
     * @var array
     */
    private $errors;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param PermissionService $permissions
     * @param URI $uri
     * @param callable $random
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        PermissionService $permissions,
        URI $uri,
        callable $random
    ) {
        $this->template = $template;
        $this->em = $em;
        $this->permissions = $permissions;
        $this->uri = $uri;

        $this->random = $random;

        $this->userRepo = $em->getRepository(User::class);
        $this->permissionRepo = $em->getRepository(UserPermission::class);
        $this->typeRepo = $em->getRepository(UserType::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $application = $request->getAttribute(Application::class);
        $user = $this->getUser($request);

        $currentUserPerms = $this->permissions->getUserPermissions($user);

        $form = [];

        if ($request->getMethod() === 'POST') {

            $form = [
                'users' => $request->getParsedBody()['users'] ?? [],
                'type' => $request->getParsedBody()['type'] ?? ''
            ];

            if ($permissions = $this->handleForm($form, $application, $currentUserPerms)) {
                $this->withFlash($request, Flash::SUCCESS, self::MSG_SUCCESS);
                return $this->withRedirectRoute($response, $this->uri, 'application', ['application' => $application->id()]);
            }
        }

        $availableTypes = $this->getAvailableTypes($currentUserPerms);

        return $this->withTemplate($request, $response, $this->template, [
            'application' => $application,
            'availableTypes' => $availableTypes,
            'users' => $this->userRepo->findBy(['isActive' => true], ['handle' => 'ASC']),

            'form' => $form,
            'errors' => $this->errors
        ]);
    }

    /**
     * @param UserPerm $perm
     *
     * @return array
     */
    private function getAvailableTypes(UserPerm $perm)
    {
        // Just show all the types, we validate who can add what when the form is submitted.
        return [
            'deploy_nonprod' => 'Deployment - Non-prod',
            'deploy_prod' => 'Deployment - Prod',
            'lead' => 'Owner',
        ];
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
     * @param UserPerm $currentPerm
     *
     * @return array|null
     */
    private function handleForm(array $data, Application $application, UserPerm $currentPerm)
    {
        $isAdmin = ($currentPerm->isSuper() || $currentPerm->isButtonPusher());

        $users = $this->parseSubmittedUsers($data['users']);
        $type = $data['type'];
        $isProd = ($type === 'deploy_prod');

        if (in_array($type, ['deploy_prod', 'deploy_nonprod'])) {
            $type = 'deploy';
        }

        if (!in_array($type, ['lead', 'deploy'])) {
            $this->errors[] = self::ERR_INVALID_TYPE;
        }

        // Only btn can add prod
        if (!$currentPerm->isButtonPusher() && $isProd) {
            $this->errors[] = self::ERR_CANNOT_ADD_PROD;
        }

        // Only admins can add leads
        if (!$isAdmin && $type === 'lead') {
            $this->errors[] = self::ERR_CANNOT_ADD_LEAD;
        }

        if (!$users) {
            $this->errors[] = self::ERR_SELECT_A_USER;
        }

        if ($this->errors) return null;

        // Verify users /  database lookup
        $verified = $this->validateUsers($users);

        if ($this->errors) return null;

        // verify no dupe permissions
        $this->ValidateDuplicatePermissions($verified, $application, $type, $isProd);

        if ($this->errors) return null;

        // save
        $perms = [];
        if ($type === 'deploy') {
            foreach ($verified as $user) {
                $perms[] = $this->savePermissions($application, $user, $isProd);
            }
        } else {
            foreach ($verified as $user) {
                $perms[] = $this->saveType($application, $user, 'lead');
            }
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
        $criteria = (new Criteria)->where(Criteria::expr()->in('handle', $users));
        $verifiedUsers = $this->userRepo
            ->matching($criteria)
            ->toArray();

        $verifiedUsernames = array_map(function($u) {
            return strtolower($u->handle());
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
     * @param bool $isProduction
     *
     * @return void
     */
    private function validateDuplicatePermissions(array $users, Application $application, $type, $isProduction)
    {
        $dupePermissions = [];

        if ($type === 'lead') {
            $criteria = (new Criteria)
                ->where(Criteria::expr()->in('user', $users))
                ->andWhere(Criteria::expr()->eq('application', $application))
                ->andWhere(Criteria::expr()->eq('type', $type));

            $dupePermissions = $this->typeRepo
                ->matching($criteria)
                ->toArray();

        } elseif ($type === 'deploy') {

            $criteria = (new Criteria)
                ->where(Criteria::expr()->in('user', $users))
                ->andWhere(Criteria::expr()->eq('isProduction', $isProduction))
                ->andWhere(Criteria::expr()->eq('application', $application));

            $dupePermissions = $this->permissionRepo
                ->matching($criteria)
                ->toArray();
        }

        $dupePermissions = array_map(function($u) {
            return $u->user()->handle();
        }, $dupePermissions);

        foreach ($dupePermissions as $username) {
            $this->errors[] = sprintf(self::ERR_DUPE_PERM, $username);
        }

        return $dupePermissions;
    }

    /**
     * @param Application $application
     * @param User $user
     * @param bool $isProd
     *
     * @return UserPermission
     */
    private function savePermissions(Application $application, User $user, $isProd)
    {
        $id = call_user_func($this->random);

        $permissions = (new UserPermission)
            ->withId($id)
            ->withIsProduction($isProd)
            ->withApplication($application)
            ->withUser($user);

        // Clear cache
        $this->permissions->clearUserCache($user);
        $this->em->persist($permissions);

        return $permissions;
    }

    /**
     * @param Application $application
     * @param User $user
     * @param string $type
     *
     * @return UserType
     */
    private function saveType(Application $application, User $user, $type)
    {
        $id = call_user_func($this->random);

        $permissions = (new UserType)
            ->withId($id)
            ->withType($type)
            ->withApplication($application)
            ->withUser($user);

        // Clear cache
        $this->permissions->clearUserCache($user);
        $this->em->persist($permissions);

        return $permissions;
    }
}
