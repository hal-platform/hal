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
use Hal\UI\Utility\ValidatorTrait;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Group;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;

class EditApplicationController implements ControllerInterface
{
    use ValidatorTrait;

    const SUCCESS = 'Application updated successfully.';

    const ERR_DUPLICATE_IDENTIFIER = 'An application with this identifier already exists.';

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
     * @var Application
     */
    private $application;

    /**
     * @var Flasher
     */
    private $flasher;

    /**
     * @var Request
     */
    private $request;

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
     * @param Application $application
     * @param Flasher $flasher
     * @param Request $request
     * @param string $githubEnterprisePrefix
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        GitHubService $github,
        Application $application,
        Flasher $flasher,
        Request $request,
        $githubEnterprisePrefix
    ) {
        $this->template = $template;

        $this->groupRepo = $em->getRepository(Group::CLASS);
        $this->applicationRepo = $em->getRepository(Application::CLASS);
        $this->em = $em;

        $this->github = $github;
        $this->application = $application;
        $this->flasher = $flasher;

        $this->request = $request;
        $this->githubEnterprisePrefix = $githubEnterprisePrefix;

        $this->errors = [];
    }

    /**
     * @inheritDoc
     */
    public function __invoke()
    {
        $context = [
            'form' => [
                'identifier' => $this->request->post('identifier') ?: $this->application->key(),
                'name' => $this->request->post('name') ?: $this->application->name(),
                'group' => $this->request->post('group') ?: $this->application->group()->id(),
                'github' => $this->request->post('github') ?: sprintf('%s/%s', $this->application->githubOwner(), $this->application->githubRepo())
            ],
            'application' => $this->application,
            'groups' => $this->groupRepo->findAll()
        ];

        if ($this->request->isPost()) {
            if ($application = $this->handleFormSubmission($this->request, $this->application)) {
                $this->em->merge($application);
                $this->em->flush();

                return $this->flasher
                    ->withFlash(self::SUCCESS, 'success')
                    ->load('application', ['application' => $this->application->id()]);
            }
        }

        $context['errors'] = $this->errors;
        $this->template->render($context);
    }

    /**
     * @param Request $request
     * @param Application $application
     *
     * @return Application|null
     */
    private function handleFormSubmission(Request $request, Application $application)
    {
        $identifier = strtolower($request->post('identifier'));
        $github = strtolower($request->post('github'));

        $this->errors = array_merge(
            $this->validateSimple($identifier, 'Identifier', 24, true),
            $this->validateText($request->post('name'), 'Name', 64, true),

            $this->validateText($request->post('group'), 'Group', 128, true),
            $this->validateText($github, 'GitHub Repository', 100, true)
        );

        if ($this->errors) return;

        if (!$group = $this->groupRepo->find($this->request->post('group'))) {
            $this->errors[] = 'Please select a group.';
        }

        if ($this->errors) return;

        if ($github !== sprintf('%s/%s', $application->githubOwner(), $application->githubRepo())) {
            $github = $this->formatGitHubFromURL($github);

            $parts = explode('/', $github);
            if (count($parts) === 2) {
                $githubOwner = $parts[0];
                $githubRepo = $parts[1];
            } else {
                $this->errors[] = self::ERR_GITHUB_INVALID_REPO;
            }

            if ($this->errors) return;
            $this->validateGithubRepo($githubOwner, $githubRepo);
        }

        if ($this->errors) return;

        // Only check for duplicate identifier if it is being changed
        if (!$this->errors && $identifier != $application->key()) {
            if ($repo = $this->applicationRepo->findOneBy(['key' => $identifier])) {
                $this->errors[] = self::ERR_DUPLICATE_IDENTIFIER;
            }
        }

        if ($this->errors) return;

        $application
            ->withKey($identifier)
            ->withName($request->post('name'))
            ->withGroup($group);

        if (isset($githubOwner)) {
            $application
                ->withGithubOwner($githubOwner)
                ->withGithubRepo($githubRepo);
        }

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
}
