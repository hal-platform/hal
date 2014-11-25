<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Repository;

use Doctrine\ORM\EntityManager;
use QL\Hal\Core\Entity\Group;
use QL\Hal\Core\Entity\Repository;
use QL\Hal\Core\Entity\Repository\GroupRepository;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Services\GithubService;
use QL\Hal\Session;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class AdminAddController
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type GroupRepository
     */
    private $groupRepo;

    /**
     * @type RepositoryRepository
     */
    private $repoRepo;

    /**
     * @type EntityManager
     */
    private $entityManager;

    /**
     * @type GithubService
     */
    private $github;

    /**
     * @type Session
     */
    private $session;

    /**
     * @type UrlHelper
     */
    private $url;

    /**
     * A list of illegal parameters to search for in system commands provided by the user.
     *
     * @var string[]
     */
    private $invalidCommandParameters;

    /**
     * @param TemplateInterface $template
     * @param GroupRepository $groupRepo
     * @param RepositoryRepository $repoRepo
     * @param EntityManager $entityManager
     * @param GithubService $github
     * @param Session $session
     * @param UrlHelper $url
     */
    public function __construct(
        TemplateInterface $template,
        GroupRepository $groupRepo,
        RepositoryRepository $repoRepo,
        EntityManager $entityManager,
        GithubService $github,
        Session $session,
        UrlHelper $url
    ) {
        $this->template = $template;
        $this->groupRepo = $groupRepo;
        $this->repoRepo = $repoRepo;
        $this->entityManager = $entityManager;
        $this->github = $github;
        $this->session = $session;
        $this->url = $url;

        $this->invalidCommandParameters = [
            '&&', // operator
            '||', // operator
            '|',  // pipe
            '>',  // redirect
            '>>', // redirect
            '<',  // redirect
            '<<'  // redirect
        ];
    }

    /**
     * @param Request $request
     * @param Response $response
     */
    public function __invoke(Request $request, Response $response)
    {
        $renderContext = [
            'form' => [
                'identifier' => $request->post('identifier'),
                'name' => $request->post('name'),
                'group' => $request->post('group'),
                'github_user' => $request->post('github_user'),
                'github_repo' => $request->post('github_repo'),
                'notification_email' => $request->post('notification_email'),
                'build_command' => $request->post('build_command'),
                'pre_command' => $request->post('pre_command'),
                'post_command' => $request->post('post_command')
            ],
            'groups' => $this->groupRepo->findBy([], ['name' => 'ASC']),
            'errors' => $this->checkFormErrors($request)
        ];

        if ($request->isPost()) {
            // this is kind of crummy
            if (!$renderContext['errors'] && !$group = $this->groupRepo->find($request->post('group'))) {
                $renderContext['errors'][] = 'Please select a group.';
            }

            if (!$renderContext['errors']) {
                $repository = $this->handleFormSubmission($request, $group);

                $message = sprintf('Repository "%s" added.', $repository->getKey());
                $this->session->flash($message, 'success');
                return $this->url->redirectFor('repositories');
            }
        }

        $rendered = $this->template->render($renderContext);
        $response->setBody($rendered);
    }

    /**
     * @param Request $request
     * @param Group $group
     * @return Repository
     */
    private function handleFormSubmission(Request $request, Group $group)
    {
        $identifier = strtolower($request->post('identifier'));
        $name = $request->post('name');
        $email = $request->post('notification_email');

        $user = $request->post('github_user');
        $repo = $request->post('github_repo');

        $repository = new Repository;
        $repository->setKey($identifier);
        $repository->setDescription($name);
        $repository->setGroup($group);
        $repository->setEmail($email);

        $repository->setGithubUser($user);
        $repository->setGithubRepo($repo);

        if ($buildCommand = $request->post('build_command')) {
            $repository->setBuildCmd($buildCommand);
        }

        $this->entityManager->persist($repository);
        $this->entityManager->flush();

        return $repository;
    }

    /**
     * @param Request $request
     * @return array
     */
    private function checkFormErrors(Request $request)
    {
        if (!$request->isPost()) {
            return [];
        }

        $human = [
            'identifier' => 'Identifier',
            'name' => 'Name',
            'group' => 'Group',
            'github_user' => 'Github User',
            'github_repo' => 'Github Repository',
            'notification_email' => 'Notification Email',
            'build_command' => 'Build Command'
        ];

        $identifier = strtolower($request->post('identifier'));

        $errors = array_merge(
            $this->validateKey($identifier, $human['identifier']),
            $this->validateText($request->post('name'), $human['name'], 64),

            $this->validateText($request->post('group'), $human['group'], 128),
            $this->validateText($request->post('github_user'), $human['github_user'], 48),
            $this->validateText($request->post('github_repo'), $human['github_repo'], 48),
            $this->validateText($request->post('notification_email'), $human['notification_email'], 128, false),

            $this->validateCommand($request->post('build_command'), $human['build_command']),

            $this->validateGithubRepo($request->post('github_user'), $request->post('github_repo'))
        );

        // check for duplicate nickname
        if (!$errors && $this->repoRepo->findOneBy(['key' => $identifier])) {
            $errors[] = 'A repository with this identifier already exists.';
        }

        return $errors;
    }

    /**
     * @param string $value
     * @param string $friendlyName
     *
     * @return array
     */
    private function validateCommand($value, $friendlyName)
    {
        $errors = $this->validateText($value, $friendlyName, 128, false);

        // Split the command into unique parameters
        $command = explode(' ', $value);
        $parameters = array_fill_keys($command, true);

        // We could easily just prevent spaces and shell escape EVERYTHING, but to add at least some flexibility
        // hal-agent will explode on spaces and shell escape individual arguments so users can pass parameters
        // to their scripts

        // Search for operators, pipes, and redirects to prevent users from doing complex bash logic directly on the HAL command.
        foreach ($this->invalidCommandParameters as $param) {
            if (isset($parameters[$param])) {
                $errors[] = 'Compound commands are not allowed. If you need complex logic, create a script within the application codebase and call that instead.';

                // If one illegal parameter is found, just return immediately.
                return $errors;
            }
        }

        return $errors;
    }

    /**
     * @param string $user
     * @param string $repo
     *
     * @return array
     */
    private function validateGithubRepo($user, $repo)
    {
        $errors = [];

        if (!$this->github->user($user)) {
            $errors[] = 'Invalid Github Enterprise user/organization';

        // elseif here so we dont bother making 2 github calls if the first one failed
        } elseif (!$this->github->repository($user, $repo)) {
            $errors[] = 'Invalid Github Enterprise repository name';
        }

        return $errors;
    }

    /**
     * @param string $identifier
     * @param string $friendlyName
     *
     * @return array
     */
    private function validateKey($identifier, $friendlyName)
    {
        $errors = [];

        if (!$identifier) {
            $errors[] = "$friendlyName is required";
        }

        if (!preg_match('@^[a-z0-9-.]*$@', $identifier)) {
            $errors[] = "$friendlyName must be be composed of lowercase alphanumeric, hyphen, and period characters";
        }

        if (mb_strlen($identifier, 'UTF-8') > 24) {
            $errors[] = "$friendlyName must be under 24 characters";
        }

        if (mb_strlen($identifier, 'UTF-8') < 2) {
            $errors[] = "$friendlyName must be more than 1 character";
        }

        return $errors;
    }

    /**
     * @param string $value
     * @param string $friendlyName
     * @param int $length
     * @param boolean $required
     *
     * @return array
     */
    private function validateText($value, $friendlyName, $length, $required = true)
    {
        $errors = [];

        if ($required && !$value) {
            $errors[] = sprintf('%s is required', $friendlyName);
        }

        if (!mb_check_encoding($value, 'UTF-8')) {
            $errors[] = sprintf('%s must be valid UTF-8', $friendlyName);
        }

        if (mb_strlen($value, 'UTF-8') > $length) {
            $errors[] = sprintf('%s must be %d characters or fewer', $friendlyName, $length);
        }

        return $errors;
    }
}
