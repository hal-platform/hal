<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Permissions;

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

    const MSG_SUCCESS = 'New permissions added for "%s".';

    const ERR_INVALID_TYPE = 'Please select a valid permission type.';
    const ERR_CANNOT_ADD_SUPER = 'You are not allowed to add super permissions.';
    const ERR_CANNOT_ADD_ADMIN = 'You are not allowed to add admin permissions.';
    const ERR_CANNOT_ADD_PROD = 'You are not allowed to add prod permissions';
    const ERR_DUPLICATE_PERMISSION = 'Cannot add permissions. This user already has this permission.';
    const ERR_DUPLICATE_LEAD = 'This user already has lead permissions for this application.';
    const ERR_DUPLICATE_DEPLOY = 'This user already has deployment permissions for this application.';
    const ERR_APPLICATION_REQUIRED = 'Application is required for lead or deployment permissions.';
    const ERR_ENVIRONMENT_REQUIRED = 'Allowed Environments is required for deployment permissions.';

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
    private $applicationRepo;

    /**
     * @var PermissionService
     */
    private $permissions;

    /**
     * @var JSON
     */
    private $json;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @var callable
     */
    private $random;

    /**
     * @var array
     */
    private $errors;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param PermissionService $permissions
     * @param JSON $json
     * @param URI $uri
     * @param callable $random
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        PermissionService $permissions,
        JSON $json,
        URI $uri,
        callable $random
    ) {
        $this->template = $template;

        $this->permissions = $permissions;
        $this->json = $json;
        $this->uri = $uri;
        $this->random = $random;

        $this->em = $em;
        $this->applicationRepo = $em->getRepository(Application::class);

        $this->errors = [];
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $currentUser = $this->getUser($request);
        $user = $request->getAttribute(User::class);

        $currentUserPerms = $this->permissions->getUserPermissions($currentUser);
        $selectedUserPerms = $this->permissions->getUserPermissions($user);

        $form = $this->getFormData($request);

        if ($permission = $this->handleForm($form, $request, $user, $currentUserPerms, $selectedUserPerms)) {

            $landingRoute = ($permission instanceof UserPermission) ? 'admin.permissions.deployment' : 'admin.permissions';

            $this->withFlash($request, Flash::SUCCESS, sprintf(self::MSG_SUCCESS, $permission->user()->handle()));
            return $this->withRedirectRoute($response, $this->uri, $landingRoute);
        }

        $availableTypes = $this->getAvailableTypes($currentUserPerms);
        $availableApplications = $this->getAvailableApplications();

        $appPerm = $this->permissions->getApplications($selectedUserPerms);

        return $this->withTemplate($request, $response, $this->template, [
            'form' => $form,
            'errors' => $this->errors,

            'user' => $user,
            'userPerm' => $selectedUserPerms,
            'leadApplications' => $appPerm['lead'],
            'prodApplications' => $appPerm['prod'],
            'nonProdApplications' => $appPerm['non_prod'],

            'availableTypes' => $availableTypes,
            'availableApplications' => $availableApplications,
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
                'is_production' => $request->getParsedBody()['is_production'] ?? ''
            ];
        } else {
            $form = [
                'type' => '',
                'application' => '',
                'is_production' => ''
            ];
        }

        return $form;
    }

    /**
     * @param UserPerm $perm
     *
     * @return array
     */
    private function getAvailableTypes(UserPerm $perm)
    {
        $availableTypes = [
            'deploy' => 'Deployment',
            'lead' => 'Owner',
            'admin' => 'Admin'
        ];

        if ($perm->isSuper()) {
            $availableTypes = [
                'pleb' => 'Standard',
                'deploy' => 'Deployment',
                'lead' => 'Owner',
                // 'admin' => 'Admin',
                'super' => 'Super'
            ];
        }

        return $availableTypes;
    }

    /**
     * @param array $data
     * @param ServerRequestInterface $request
     * @param User $user
     * @param UserPerm $currentPerm
     * @param UserPerm $selectedPerm
     *
     * @return UserType|UserPermission|null
     */
    private function handleForm(array $data, ServerRequestInterface $request, User $user, UserPerm $currentPerm, UserPerm $selectedPerm)
    {
        if ($request->getMethod() !== 'POST') {
            return null;
        }

        $type = $data['type'];
        $appId = $data['application'];
        $isProd = $data['is_production'];

        $realMap = [
            'pleb' => 'pleb',
            'lead' => 'lead',
            'admin' => 'btn_pusher',
            'super' => 'super'
        ];

        if (!in_array($type, ['pleb', 'lead', 'admin', 'super', 'deploy'])) {
            $this->errors[] = self::ERR_INVALID_TYPE;
        }

        // Only supers can add supers
        if (!$currentPerm->isSuper() && $type === 'super') {
            $this->errors[] = self::ERR_CANNOT_ADD_SUPER;
        }

        // Only admins can add admins
        if (!$currentPerm->isButtonPusher() && $type === 'admin') {
            $this->errors[] = self::ERR_CANNOT_ADD_ADMIN;
        }

        // If super, cannot add prod/deploy permission
        if (!$currentPerm->isButtonPusher() && $type === 'deploy' && $isProd === 'true') {
            $this->errors[] = self::ERR_CANNOT_ADD_PROD;
        }

        // If already pleb, admin, super. reject.
        if ($type === 'pleb' && $selectedPerm->isPleb()) {
            $this->errors[] = self::ERR_DUPLICATE_PERMISSION;
        }
        if ($type === 'admin' && $selectedPerm->isButtonPusher()) {
            $this->errors[] = self::ERR_DUPLICATE_PERMISSION;
        }
        if ($type === 'super' && $selectedPerm->isSuper()) {
            $this->errors[] = self::ERR_DUPLICATE_PERMISSION;
        }

        if ($type === 'lead' && !$appId) {
            $this->errors[] = self::ERR_APPLICATION_REQUIRED;
        }

        if ($type === 'deploy' && !in_array($isProd, ['true', 'false'], true)) {
            $this->errors[] = self::ERR_ENVIRONMENT_REQUIRED;
        }

        if ($this->errors) return null;

        if ($type === 'lead' || $type === 'deploy') {
            if (!$application = $this->applicationRepo->find($appId)) {
                $this->errors[] = self::ERR_APPLICATION_REQUIRED;
            }
        } else {
            $application = null;
        }

        if ($this->errors) return null;

        // Duplicate deployment permissions
        if ($type === 'deploy') {
            $isProd = ($isProd === 'true') ? true : false;
            if ($isProd && $selectedPerm->canDeployApplicationToProd($application)) {
                $this->errors[] = self::ERR_DUPLICATE_DEPLOY;

            } elseif (!$isProd && $selectedPerm->canDeployApplicationToNonProd($application)) {
                $this->errors[] = self::ERR_DUPLICATE_DEPLOY;
            }
        }

        // Duplicate lead permissions
        if ($application && $selectedPerm->isLeadOfApplication($application)) {
            $this->errors[] = self::ERR_DUPLICATE_LEAD;
        }

        if ($this->errors) return null;

        if ($type === 'deploy') {
            return $this->savePermissions($user, $application, $isProd);
        } else {
            return $this->saveType($user, $application, $realMap[$type]);
        }
    }

    /**
     * @param User $user
     * @param Application $application
     * @param bool $isProd
     *
     * @return UserPermission
     */
    private function savePermissions(User $user, Application $application, $isProd): UserPermission
    {
        $id = call_user_func($this->random);

        $permissions = (new UserPermission)
            ->withId($id)
            ->withIsProduction($isProd)
            ->withApplication($application)
            ->withUser($user);

        // Clear cache
        $this->permissions->clearUserCache($user);

        // persist to database
        $this->em->persist($permissions);
        $this->em->flush();

        return $permissions;
    }

    /**
     * @param User $user
     * @param Application|null $application
     * @param string $type
     *
     * @return UserType
     */
    private function saveType(User $user, ?Application $application, $type): UserType
    {
        $id = call_user_func($this->random);

        $permissions = (new UserType)
            ->withId($id)
            ->withType($type)
            ->withUser($user);

        if ($application) {
            $permissions->withApplication($application);
        }

        // Clear cache
        $this->permissions->clearUserCache($user);

        // persist to database
        $this->em->persist($permissions);
        $this->em->flush();

        return $permissions;
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

        $applications = $this->applicationRepo->findBy([], ['name' => 'ASC']);

        $data = [];
        foreach ($applications as $app) {
            $group = $app->group()->name();

            if (!isset($data[$group])) {
                $data[$group] = [];
            }

            $data[$group][$app->id()] = $app->name();
        }

        uksort($data, function ($a, $b) {
            return strcasecmp($a, $b);
        });

        $this->setToCache(self::CACHE_KEY_PERMISSION_APPLICATIONS, $this->json->encode($data));

        return $data;
    }
}
