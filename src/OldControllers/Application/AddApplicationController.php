<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Application;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Flasher;
use Hal\UI\Service\GitHubService;
use Hal\UI\Service\PermissionService;
use Hal\UI\Utility\ValidatorTrait;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Group;
use QL\Hal\Core\Entity\User;
use QL\Hal\Core\Entity\UserType;
use QL\Hal\Core\Type\EnumType\UserTypeEnum;
use QL\Hal\Core\Utility\SortingTrait;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;

class AddApplicationController implements ControllerInterface
{
    use SortingTrait;
    use ValidatorTrait;

    const SUCCESS = 'Application "%s" added.';

    const ERR_INVALID_GROUP = 'Please select a group.';
    const ERR_NO_GROUPS = 'An application requires an group. Groups must be added before applications.';

    const ERR_DUPE_IDENTIFIER = 'An application with this identifier already exists.';

    const ERR_GITHUB_INVALID_OWNER = 'Invalid GitHub Enterprise user or organization.';
    const ERR_GITHUB_INVALID_REPO = 'Invalid GitHub Enterprise Repository';

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityRepository
     */
    private $groupRepo;
    private $applicationRepo;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var GitHubService
     */
    private $github;

    /**
     * @var PermissionService
     */
    private $permissionService;

    /**
     * @var Flasher
     */
    private $flasher;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var User
     */
    private $currentUser;

    /**
     * @var callable
     */
    private $random;

    /**
     * @var string
     */
    private $githubEnterprisePrefix;

    /**
     * @var array
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
     * @param string $githubEnterprisePrefix
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        GitHubService $github,
        PermissionService $permissionService,
        Flasher $flasher,
        Request $request,
        User $currentUser,
        callable $random,
        $githubEnterprisePrefix
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
        $this->githubEnterprisePrefix = $githubEnterprisePrefix;

        $this->errors = [];
    }

    /**
     * @inheritDoc
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
            'groups' => $groups
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
            $data['github']
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
    private function validateForm($identifier, $name, $groupID, $github)
    {
        $this->errors = array_merge(
            $this->validateSimple($identifier, 'Identifier', 24, true),
            $this->validateText($name, 'Name', 64, true),
            $this->validateText($groupID, 'Group', 128, true),
            $this->validateText($github, 'GitHub Repository', 100, true)
        );

        if ($this->errors) return;

        $github = $this->formatGitHubFromURL($github);

        if ($this->errors) return;

        // in team/project format
        $parts = explode('/', $github);
        if (count($parts) === 2) {
            $githubOwner = $parts[0];
            $githubRepo = $parts[1];
        } else {
            $this->errors[] = self::ERR_GITHUB_INVALID_REPO;
        }

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
        if (!$this->github->user($owner)) {
            $this->errors[] = self::ERR_GITHUB_INVALID_OWNER;

        // elseif here so we dont bother making 2 github calls if the first one failed
        } elseif (!$this->github->repository($owner, $repo)) {
            $this->errors[] = self::ERR_GITHUB_INVALID_REPO;
        }
    }

    /**
     * Parse user/repo from the provided input which may or may not be a full github URL.
     *
     * @param string $github
     *
     * @return string
     */
    private function formatGitHubFromURL($github)
    {
        if (stripos($github, 'http://') === 0) {
            // is URL
            if (stripos($github, $this->githubEnterprisePrefix) === 0) {
                $github = substr($github, strlen($this->githubEnterprisePrefix));

                if (substr($github, -4) === '.git') {
                    $github = substr($github, 0, -4);
                }

            } else {
                $this->errors[] = self::ERR_GITHUB_INVALID_REPO;
            }
        }

        return $github;
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

            'github' => strtolower($this->request->post('github'))
        ];

        return $form;
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