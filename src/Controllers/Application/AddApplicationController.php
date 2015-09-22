<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Application;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use MCP\Cache\CachingTrait;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Group;
use QL\Hal\Core\Entity\User;
use QL\Hal\Core\Entity\UserType;
use QL\Hal\Core\Type\EnumType\UserTypeEnum;
use QL\Hal\Core\Utility\SortingTrait;
use QL\Hal\Flasher;
use QL\Hal\Service\GitHubService;
use QL\Hal\Service\PermissionService;
use QL\Hal\Utility\ValidatorTrait;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;

class AddApplicationController implements ControllerInterface
{
    use CachingTrait;
    use SortingTrait;
    use ValidatorTrait;

    const CACHE_KEY_ORGANIZATIONS = 'page:github.organizations';

    const SUCCESS = 'Application "%s" added.';

    const ERR_INVALID_GROUP = 'Please select a group.';
    const ERR_NO_GROUPS = 'An application requires an group. Groups must be added before applications.';

    const ERR_DUPE_IDENTIFIER = 'An application with this identifier already exists.';

    const ERR_GITHUB_INVALID_ORG = 'Invalid Github Enterprise organization';
    const ERR_GITHUB_INVALID_REPO = 'Invalid Github Enterprise repository name';

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type EntityRepository
     */
    private $groupRepo;
    private $applicationRepo;

    /**
     * @type EntityManagerInterface
     */
    private $em;

    /**
     * @type GitHubService
     */
    private $github;

    /**
     * @type PermissionService
     */
    private $permissionService;

    /**
     * @type Flasher
     */
    private $flasher;

    /**
     * @type Request
     */
    private $request;

    /**
     * @type User
     */
    private $currentUser;

    /**
     * @type callable
     */
    private $random;

    /**
     * @type array
     */
    private $errors;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param GitHubService $github
     * @param PermissionService $permissionService
     * @param Flasher $flasher
     * @param Request $request
     * @param User $currentUser
     * @param callable $random
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        GitHubService $github,
        PermissionService $permissionService,
        Flasher $flasher,
        Request $request,
        User $currentUser,
        callable $random
    ) {
        $this->template = $template;

        $this->groupRepo = $em->getRepository(Group::CLASS);
        $this->applicationRepo = $em->getRepository(Application::CLASS);
        $this->em = $em;

        $this->github = $github;
        $this->permissionService = $permissionService;
        $this->flasher = $flasher;

        $this->request = $request;
        $this->currentUser = $currentUser;
        $this->random = $random;

        $this->errors = [];
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        if (!$groups = $this->groupRepo->findAll()) {
            return $this->flasher
                ->withFlash(self::ERR_NO_GROUPS, 'error')
                ->load('group.add');
        }

        $form = $this->data();

        if ($application = $this->handleForm($form)) {
            $message = sprintf(self::SUCCESS, $application->key());
            return $this->flasher
                ->withFlash($message, 'success')
                ->load('applications');
        }

        usort($groups, $this->groupSorter());

        $context = [
            'form' => $form,
            'errors' => $this->errors,
            'groups' => $groups,
            'github_orgs' => $this->getOrganizations()
        ];

        $this->template->render($context);
    }

    /**
     * @param array $data
     *
     * @return Application|null
     */
    private function handleForm(array $data)
    {
        if (!$this->request->isPost()) {
            return null;
        }

        $application = $this->validateForm(
            $data['identifier'],
            $data['name'],
            $data['group'],
            $data['github_user'],
            $data['github_repo']
        );

        if ($application) {
            $this->makeLeadMaybeQuestionMarkIDontKnow($application);

            // persist to database
            $this->em->persist($application);
            $this->em->flush();
        }

        return $application;
    }

    /**
     * @param string $identifier
     * @param string $name
     * @param string $groupID
     * @param string $githubOwner
     * @param string $githubRepo
     *
     * @return Application|null
     */
    private function validateForm($identifier, $name, $groupID, $githubOwner, $githubRepo)
    {
        $this->errors = array_merge(
            $this->validateSimple($identifier, 'Identifier', 24, true),
            $this->validateText($name, 'Name', 64, true),

            $this->validateText($githubOwner, 'GitHub Organization', 48, true),
            $this->validateText($githubRepo, 'GitHub Repository', 48, true)
        );

        if ($this->errors) return;

        $this->validateGithubRepo($githubOwner, $githubRepo);

        if ($this->errors) return;

        // check for duplicate key
        if ($dupe = $this->applicationRepo->findOneBy(['key' => $identifier])) {
            $this->errors[] = self::ERR_DUPE_IDENTIFIER;
        }

        // check for duplicate key
        if (!$group = $this->groupRepo->find($groupID)) {
            $this->errors[] = self::ERR_INVALID_GROUP;
        }

        if ($this->errors) return;

        $application = (new Application)
            ->withKey($identifier)
            ->withName($name)
            ->withGroup($group)

            ->withGithubOwner($githubOwner)
            ->withGithubRepo($githubRepo)
            ->withEmail('');

        // Default to blank, not null
        $application->setBuildCmd('');
        $application->setBuildTransformCmd('');
        $application->setPrePushCmd('');
        $application->setPostPushCmd('');

        return $application;
    }

    /**
     * @param string $owner
     * @param string $repo
     *
     * @return void
     */
    private function validateGithubRepo($owner, $repo)
    {
        if (!$this->github->organization($owner)) {
            $this->errors[] = self::ERR_GITHUB_INVALID_ORG;

        // elseif here so we dont bother making 2 github calls if the first one failed
        } elseif (!$this->github->repository($owner, $repo)) {
            $this->errors[] = self::ERR_GITHUB_INVALID_REPO;
        }
    }

    /**
     * @return array
     */
    private function data()
    {
        $form = [
            'identifier' => strtolower($this->request->post('identifier')),
            'name' => $this->request->post('name'),
            'group' => $this->request->post('group'),

            'github_user' => strtolower($this->request->post('github_user')),
            'github_repo' => strtolower($this->request->post('github_repo'))
        ];

        return $form;
    }

    /**
     * @return array
     */
    private function getOrganizations()
    {
        // external cache
        if ($cached = $this->getFromCache(self::CACHE_KEY_ORGANIZATIONS)) {
            if (is_array($cached)) {
                return $cached;
            }
        }

        $apps = $this->applicationRepo->findAll();

        $activeOrgs = [];
        foreach ($apps as $app) {
            $activeOrgs[$app->githubOwner()] = true;
        }

        $activeOrgs = array_keys($activeOrgs);

        $active = $other = [];
        $orgs = $this->github->organizations();

        foreach ($orgs as $org) {
            $owner = strtolower($org['login']);
            if (in_array($owner, $activeOrgs)) {
                $active[] = $org['login'];
            } else {
                $other[] = $org['login'];
            }
        }

        $sorter = function($a, $b) {
            return strcasecmp($a, $b);
        };

        usort($active, $sorter);
        usort($other, $sorter);

        $data = [
            'active' => $active,
            'other' => $other
        ];

        $this->setToCache(self::CACHE_KEY_ORGANIZATIONS, $data);
        return $data;
    }

    /**
     * @return void
     */
    private function makeLeadMaybeQuestionMarkIDontKnow(Application $application)
    {
        $perms = $this->permissionService->getUserPermissions($this->currentUser);

        if ($perms->isButtonPusher() || $perms->isSuper()) {
            return;
        }

        $id = call_user_func($this->random);
        $permissions = (new UserType)
            ->withId($id)
            ->withType(UserTypeEnum::TYPE_LEAD)
            ->withUser($this->currentUser)
            ->withApplication($application);

        // Clear cache
        $this->permissionService->clearUserCache($this->currentUser);

        // persist to database
        $this->em->persist($permissions);
    }
}
