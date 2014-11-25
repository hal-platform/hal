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
use QL\Hal\Session;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class AdminEditController
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
     * @type string[]
     */
    private $invalidCommandParameters;

    /**
     * @param TemplateInterface $template
     * @param Layout $layout
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
        Session $session,
        UrlHelper $url
    ) {
        $this->template = $template;
        $this->groupRepo = $groupRepo;
        $this->repoRepo = $repoRepo;
        $this->entityManager = $entityManager;
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
     * @param array $params
     * @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        if (!$repo = $this->repoRepo->find($params['id'])) {
            return $notFound();
        }

        $renderContext = [
            'form' => [
                'identifier' => $request->post('identifier') ?: $repo->getKey(),
                'name' => $request->post('name') ?: $repo->getDescription(),
                'group' => $request->post('group') ?: $repo->getGroup()->getId(),
                'notification_email' => $request->post('notification_email') ?: $repo->getEmail(),

                'build_command' => $request->post('build_command') ?: $repo->getBuildCmd(),
                'post_command' => $request->post('post_command') ?: $repo->getPostPushCmd()
            ],
            'repository' => $repo,
            'groups' => $this->groupRepo->findAll(),
            'errors' => $this->checkFormErrors($request, $repo)
        ];

        if ($request->isPost()) {
            // this is kind of crummy
            if (!$renderContext['errors'] && !$group = $this->groupRepo->find($request->post('group'))) {
                $renderContext['errors'][] = 'Please select a group.';
            }

            if (!$renderContext['errors']) {
                $repository = $this->handleFormSubmission($request, $repo, $group);

                $this->session->flash('Repository updated successfully.', 'success');
                return $this->url->redirectFor('repository', ['id' => $repo->getId()]);
            }
        }

        $rendered = $this->template->render($renderContext);
        $response->setBody($rendered);
    }

    /**
     * @param Request $request
     * @param Repository $repository
     * @param Group $group
     * @return Repository
     */
    private function handleFormSubmission(Request $request, Repository $repository, Group $group)
    {
        $identifier = strtolower($request->post('identifier'));
        $name = $request->post('name');
        $email = $request->post('notification_email');

        $buildCommand = $request->post('build_command');
        $postCommand = $request->post('post_command');

        $repository->setKey($identifier);
        $repository->setDescription($name);
        $repository->setGroup($group);
        $repository->setEmail($email);

        $repository->setBuildCmd($buildCommand);
        $repository->setPostPushCmd($postCommand);

        $this->entityManager->merge($repository);
        $this->entityManager->flush();

        return $repository;
    }

    /**
     * @param Request $request
     * @param Repository $repository
     * @return array
     */
    private function checkFormErrors(Request $request, Repository $repository)
    {
        if (!$request->isPost()) {
            return [];
        }

        $human = [
            'identifier' => 'Identifier',
            'name' => 'Name',
            'group' => 'Group',
            'notification_email' => 'Notification Email',
            'build_command' => 'Build Command',
            'post_command' => 'Post Push Command'
        ];

        $identifier = strtolower($request->post('identifier'));

        $errors = array_merge(
            $this->validateKey($request->post('identifier'), $human['identifier']),
            $this->validateText($request->post('name'), $human['name'], 64),

            $this->validateText($request->post('group'), $human['group'], 128),
            $this->validateText($request->post('notification_email'), $human['notification_email'], 128, false),

            $this->validateCommand($request->post('build_command'), $human['build_command']),
            $this->validateCommand($request->post('post_command'), $human['post_command'])
        );

        // Only check for duplicate identifier if it is being changed
        if (!$errors && $identifier != $repository->getKey()) {
            if ($repo = $this->repoRepo->findOneBy(['key' => $identifier])) {
                $errors[] = 'A repository with this identifier already exists.';
            }
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
