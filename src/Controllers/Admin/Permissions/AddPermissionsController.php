<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Admin\Permissions;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use MCP\Cache\CachingTrait;
use QL\Hal\Core\Entity\Repository;
use QL\Hal\Core\Entity\User;
use QL\Hal\Core\Entity\UserType;
use QL\Hal\Flasher;
use QL\Hal\Service\NewPermissionsService;
use QL\Hal\Service\UserPerm;
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
    const ERR_CANNOT_ADD_SUPER = 'Invalid permission type selected.';
    const ERR_DUPLICATE_PERMISSION = 'Cannot add permissions. This user already has this permission.';
    const ERR_DUPLICATE_LEAD = 'Cannot add permissions. This user already has lead permissions for this application.';
    const ERR_APPLICATION_REQUIRED = 'Application is required if adding lead permissions.';

    /**
     * @type Request
     */
    private $request;

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type User
     */
    private $currentUser;

    /**
     * @type User
     */
    private $selectedUser;

    /**
     * @type EntityManagerInterface
     */
    private $em;

    /**
     * @type EntityRepository
     */
    private $applicationRepo;

    /**
     * @type NewPermissionsService
     */
    private $permissions;

    /**
     * @type Json
     */
    private $json;

    /**
     * @type Flasher
     */
    private $flasher;

    /**
     * @type callable
     */
    private $random;

    /**
     * @type array
     */
    private $errors;

    /**
     * @param Request $request
     * @param TemplateInterface $template
     * @param User $currentUser
     * @param User $selectedUser
     * @param EntityManagerInterface $em
     * @param NewPermissionsService $permissions
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
        NewPermissionsService $permissions,
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
        $this->applicationRepo = $em->getRepository(Repository::CLASS);

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
                'application' => $this->request->post('application')
            ];

            if ($permission = $this->handleForm($currentUserPerms, $selectedUserPerms)) {
                return $this->flasher
                    ->withFlash(sprintf(self::SUCCESS, $permission->user()->getHandle()))
                    ->load('admin.permissions');
            }
        }

        $availableTypes = $this->getAvailableTypes($currentUserPerms);
        $availableApplications = $this->getAvailableApplications();

        $leadApps = $this->getLeadApplications($availableApplications, $selectedUserPerms);

        $rendered = $this->template->render([
            'form' => $form,
            'errors' => $this->errors,

            'selectedUser' => $this->selectedUser,
            'userPerm' => $selectedUserPerms,
            'leadApplications' => $leadApps,

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
            'lead' => 'Lead',
            'admin' => 'Admin'
        ];

        if ($perm->isSuper()) {
            $availableTypes = array_merge(
                ['pleb' => 'Standard'],
                $availableTypes,
                ['super' => 'Super']
            );
        }

        return $availableTypes;
    }

    /**
     * @param UserPerm $currentPerm
     * @param UserPerm $selectedPerm
     *
     * @return UserType|null
     */
    private function handleForm(UserPerm $currentPerm, UserPerm $selectedPerm)
    {
        $type = $this->request->post('type');
        $appId = $this->request->post('application');

        $realMap = [
            'pleb' => 'pleb',
            'lead' => 'lead',
            'admin' => 'btn_pusher',
            'super' => 'super'
        ];

        if (!in_array($type, ['pleb', 'lead', 'admin', 'super'])) {
            $this->errors[] = self::ERR_INVALID_TYPE;
        }

        // Only supers can add supers
        if (!$currentPerm->isSuper() && $type === 'super') {
            $this->errors[] = self::ERR_CANNOT_ADD_SUPER;
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

        if ($this->errors) return null;

        if ($type === 'lead') {
            if (!$application = $this->applicationRepo->find($appId)) {
                $this->errors[] = self::ERR_APPLICATION_REQUIRED;
            }
        } else {
            $application = null;
        }

        if ($application && in_array($application->getId(), $selectedPerm->applications())) {
            $this->errors[] = self::ERR_DUPLICATE_LEAD;
        }

        if ($this->errors) return null;

        return $this->savePermissions($realMap[$type], $application);
    }


    /**
     * @param string $type
     * @param Repository $application
     *
     * @return UserType
     */
    private function savePermissions($type, Repository $application = null)
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
            $data[$app->getId()] = $app->getName();
        }

        $this->setToCache(self::CACHE_KEY_PERMISSION_APPLICATIONS, $this->json->encode($data));
        return $data;
    }

    /**
     * @param array $availableApps
     * @param UserPerm $perm
     *
     * @return array
     */
    private function getLeadApplications(array $availableApps, UserPerm $perm)
    {
        if (!$perm->isLead()) {
            return [];
        }

        if (!$perm->applications()) {
            return [];
        }

        $apps = [];

        foreach ($perm->applications() as $app) {
            if (isset($availableApps[$app])) {
                $apps[] = [
                    'id' => $app,
                    'name' => $availableApps[$app]
                ];
            }
        }

        return $apps;
    }
}
