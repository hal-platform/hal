<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Controllers\Admin\Permissions;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\User;
use QL\Hal\Core\Entity\UserPermission;
use QL\Hal\Core\Entity\UserType;
use QL\Hal\Flasher;
use QL\Hal\Service\PermissionService;
use QL\Hal\Service\UserPerm;
use QL\MCP\Cache\CachingTrait;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\Json;
use Slim\Http\Request;

class AddPermissionsController implements ControllerInterface
{
    use CachingTrait;

    const CACHE_KEY_PERMISSION_APPLICATIONS = 'page:db.applications';

    const SUCCESS = 'New permissions added for "%s".';

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
     * @var Request
     */
    private $request;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var User
     */
    private $currentUser;

    /**
     * @var User
     */
    private $selectedUser;

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
     * @var Json
     */
    private $json;

    /**
     * @var Flasher
     */
    private $flasher;

    /**
     * @var callable
     */
    private $random;

    /**
     * @var array
     */
    private $errors;

    /**
     * @param Request $request
     * @param TemplateInterface $template
     * @param User $currentUser
     * @param User $selectedUser
     * @param EntityManagerInterface $em
     * @param PermissionService $permissions
     * @param Json $json
     * @param Flasher $flasher
     * @param callable $random
     */
    public function __construct(
        Request $request,
        TemplateInterface $template,
        User $currentUser,
        User $selectedUser,
        EntityManagerInterface $em,
        PermissionService $permissions,
        Json $json,
        Flasher $flasher,
        callable $random
    ) {
        $this->request = $request;
        $this->template = $template;
        $this->currentUser = $currentUser;
        $this->selectedUser = $selectedUser;

        $this->permissions = $permissions;
        $this->json = $json;
        $this->flasher = $flasher;
        $this->random = $random;

        $this->em = $em;
        $this->applicationRepo = $em->getRepository(Application::CLASS);

        $this->errors = [];
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $currentUserPerms = $this->permissions->getUserPermissions($this->currentUser);
        $selectedUserPerms = $this->permissions->getUserPermissions($this->selectedUser);

        $form = [];

        if ($this->request->isPost()) {
            $form = [
                'type' => $this->request->post('type'),
                'application' => $this->request->post('application'),
                'is_production' => $this->request->post('is_production')
            ];

            if ($permission = $this->handleForm($currentUserPerms, $selectedUserPerms)) {

                $landingUrl = ($permission instanceof UserPermission) ? 'admin.permissions.deployment' : 'admin.permissions';

                return $this->flasher
                    ->withFlash(sprintf(self::SUCCESS, $permission->user()->handle()))
                    ->load($landingUrl);
            }
        }

        $availableTypes = $this->getAvailableTypes($currentUserPerms);
        $availableApplications = $this->getAvailableApplications();

        $appPerm = $this->permissions->getApplications($selectedUserPerms);

        $rendered = $this->template->render([
            'form' => $form,
            'errors' => $this->errors,

            'user' => $this->selectedUser,
            'userPerm' => $selectedUserPerms,
            'leadApplications' => $appPerm['lead'],
            'prodApplications' => $appPerm['prod'],
            'nonProdApplications' => $appPerm['non_prod'],

            'availableTypes' => $availableTypes,
            'availableApplications' => $availableApplications,
        ]);
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
            'lead' => 'Lead',
            'admin' => 'Admin'
        ];

        if ($perm->isSuper()) {
            $availableTypes = [
                'pleb' => 'Standard',
                'deploy' => 'Deployment',
                'lead' => 'Lead',
                // 'admin' => 'Admin',
                'super' => 'Super'
            ];
        }

        return $availableTypes;
    }

    /**
     * @param UserPerm $currentPerm
     * @param UserPerm $selectedPerm
     *
     * @return UserType|UserPermission|null
     */
    private function handleForm(UserPerm $currentPerm, UserPerm $selectedPerm)
    {
        $type = $this->request->post('type');
        $appId = $this->request->post('application');
        $isProd = $this->request->post('is_production');

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
            return $this->savePermissions($application, $isProd);
        } else {
            return $this->saveType($realMap[$type], $application);
        }
    }

    /**
     * @param Application $application
     * @param bool $isProd
     *
     * @return UserPermission
     */
    private function savePermissions(Application $application, $isProd)
    {
        $id = call_user_func($this->random);

        $permissions = (new UserPermission)
            ->withId($id)
            ->withIsProduction($isProd)
            ->withApplication($application)
            ->withUser($this->selectedUser);

        // Clear cache
        $this->permissions->clearUserCache($this->selectedUser);

        // persist to database
        $this->em->persist($permissions);
        $this->em->flush();

        return $permissions;
    }

    /**
     * @param string $type
     * @param Application $application
     *
     * @return UserType
     */
    private function saveType($type, Application $application = null)
    {
        $id = call_user_func($this->random);

        $permissions = (new UserType)
            ->withId($id)
            ->withType($type)
            ->withUser($this->selectedUser);

        if ($application) {
            $permissions->withApplication($application);
        }

        // Clear cache
        $this->permissions->clearUserCache($this->selectedUser);

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
