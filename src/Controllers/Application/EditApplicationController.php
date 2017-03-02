<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Application;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Flash;
use Hal\UI\Service\GitHubService;
use Hal\UI\Utility\ValidatorTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Group;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\URI;

class EditApplicationController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;
    use ValidatorTrait;

    const MSG_SUCCESS = 'Application updated successfully.';

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
     * @var URI
     */
    private $uri;

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
     * @param URI $uri
     * @param string $githubEnterprisePrefix
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        GitHubService $github,
        URI $uri,
        $githubEnterprisePrefix
    ) {
        $this->template = $template;

        $this->groupRepo = $em->getRepository(Group::class);
        $this->applicationRepo = $em->getRepository(Application::class);
        $this->em = $em;

        $this->github = $github;
        $this->uri = $uri;

        $this->githubEnterprisePrefix = $githubEnterprisePrefix;

        $this->errors = [];
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $application = $request->getAttribute(Application::class);

        $form = $this->getFormData($request, $application);

        if ($modified = $this->handleForm($form, $request, $application)) {
            $this->withFlash($request, Flash::SUCCESS, self::MSG_SUCCESS);
            return $this->withRedirectRoute($response, $this->uri, 'application', ['application' => $modified->id()]);
        }

        return $this->withTemplate($request, $response, $this->template, [
            'form' => $form,
            'errors' => $this->errors,

            'application' => $application,
            'groups' => $this->groupRepo->findAll()
        ]);
    }

    /**
     * @param array $data
     * @param ServerRequestInterface $request
     * @param Application $application
     *
     * @return Application|null
     */
    private function handleForm(array $data, ServerRequestInterface $request, Application $application): ?Application
    {
        if ($request->getMethod() !== 'POST') {
            return null;
        }

        $identifier = strtolower($data['identifier']);
        $github = strtolower($data['github']);

        $this->errors = array_merge(
            $this->validateSimple($identifier, 'Identifier', 24, true),
            $this->validateText($data['name'], 'Name', 64, true),

            $this->validateText($data['group'], 'Group', 128, true),
            $this->validateText($github, 'GitHub Repository', 100, true)
        );

        if ($this->errors) return null;

        if (!$group = $this->groupRepo->find($data['group'])) {
            $this->errors[] = 'Please select a group.';
        }

        if ($this->errors) return null;

        if ($github !== sprintf('%s/%s', $application->githubOwner(), $application->githubRepo())) {
            $github = $this->formatGitHubFromURL($github);

            $parts = explode('/', $github);
            if (count($parts) === 2) {
                $githubOwner = $parts[0];
                $githubRepo = $parts[1];
            } else {
                $this->errors[] = self::ERR_GITHUB_INVALID_REPO;
            }

            if ($this->errors) return null;
            $this->validateGithubRepo($githubOwner, $githubRepo);
        }

        if ($this->errors) return null;

        // Only check for duplicate identifier if it is being changed
        if (!$this->errors && $identifier != $application->key()) {
            if ($repo = $this->applicationRepo->findOneBy(['key' => $identifier])) {
                $this->errors[] = self::ERR_DUPLICATE_IDENTIFIER;
            }
        }

        if ($this->errors) return null;

        $application
            ->withKey($identifier)
            ->withName($data['name'])
            ->withGroup($group);

        if (isset($githubOwner)) {
            $application
                ->withGithubOwner($githubOwner)
                ->withGithubRepo($githubRepo);
        }

        // persist to database
        $this->em->merge($application);
        $this->em->flush();

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
     * @param ServerRequestInterface $request
     * @param Application $application
     *
     * @return array
     */
    private function getFormData(ServerRequestInterface $request, Application $application)
    {
        if ($request->getMethod() === 'POST') {
            $form = [
                'identifier' => $request->getParsedBody()['identifier'] ?? '',
                'name' => $request->getParsedBody()['name'] ?? '',
                'group' => $request->getParsedBody()['group'] ?? '',
                'github' => $request->getParsedBody()['github'] ?? ''
            ];
        } else {
            $form = [
                'identifier' => $application->key(),
                'name' => $application->name(),
                'group' => $application->group()->id(),
                'github' => sprintf('%s/%s', $application->githubOwner(), $application->githubRepo())
            ];
        }

        return $form;
    }
}
