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
use QL\Hal\Layout;
use QL\Hal\Session;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

class AdminEditController
{
    /**
     * @var Twig_Template
     */
    private $template;

    /**
     * @var Layout
     */
    private $layout;

    /**
     * @var GroupRepository
     */
    private $groupRepo;

    /**
     * @var RepositoryRepository
     */
    private $repoRepo;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var UrlHelper
     */
    private $url;

    /**
     * @param Twig_Template $template
     * @param Layout $layout
     * @param GroupRepository $groupRepo
     * @param RepositoryRepository $repoRepo
     * @param EntityManager $entityManager
     * @param GithubService $github
     * @param Session $session
     * @param UrlHelper $url
     */
    public function __construct(
        Twig_Template $template,
        Layout $layout,
        GroupRepository $groupRepo,
        RepositoryRepository $repoRepo,
        EntityManager $entityManager,
        Session $session,
        UrlHelper $url
    ) {
        $this->template = $template;
        $this->layout = $layout;
        $this->groupRepo = $groupRepo;
        $this->repoRepo = $repoRepo;
        $this->entityManager = $entityManager;
        $this->session = $session;
        $this->url = $url;
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
                'nickname' => $request->post('nickname') ?: $repo->getKey(),
                'group' => $request->post('group') ?: $repo->getGroup()->getId(),
                'notification_email' => $request->post('notification_email') ?: $repo->getEmail(),
                'build_command' => $request->post('build_command') ?: $repo->getBuildCmd(),
                'pre_command' => $request->post('pre_command') ?: $repo->getPrePushCmd(),
                'post_command' => $request->post('post_command') ?: $repo->getPostPushCmd(),
                'description' => $request->post('description') ?: $repo->getDescription()
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

                $this->session->addFlash('Repository updated successfully.', 'repository-edit');
                return $this->url->redirectFor('repository', ['id' => $repo->getId()]);
            }
        }

        $rendered = $this->layout->render($this->template, $renderContext);
        $response->body($rendered);
    }

    /**
     * @param Request $request
     * @param Repository $repository
     * @param Group $group
     * @return Repository
     */
    private function handleFormSubmission(Request $request, Repository $repository, Group $group)
    {
        $nickname = $request->post('nickname');
        $email = $request->post('notification_email');
        $description = $request->post('description');

        $buildCommand = $request->post('build_command');
        $preCommand = $request->post('pre_command');
        $postCommand = $request->post('post_command');

        $repository->setKey($nickname);
        $repository->setGroup($group);
        $repository->setEmail($email);
        $repository->setDescription($description);

        $repository->setBuildCmd($buildCommand);
        $repository->setPrePushCmd($preCommand);
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
            'nickname' => 'Nickname',
            'group' => 'Group',
            'notification_email' => 'Notification E-mail',
            'build_command' => 'Build Command',
            'pre_command' => 'Pre Push Command',
            'post_command' => 'Post Push Command',
            'description' => 'Description'
        ];

        $errors = array_merge(
            $this->validateNickname($request->post('nickname')),
            $this->validateText($request->post('group'), $human['group'], 128),
            $this->validateText($request->post('notification_email'), $human['notification_email'], 128),
            $this->validateText($request->post('description'), $human['description'], 255),

            $this->validateText($request->post('build_command'), $human['build_command'], 255, false),
            $this->validateText($request->post('pre_command'), $human['post_command'], 128, false),
            $this->validateText($request->post('post_command'), $human['post_command'], 128, false)
        );

        // Only check for duplicate nickname if it is being changed
        if (!$errors && $request->post('nickname') != $repository->getKey()) {
            if ($repo = $this->repoRepo->findOneBy(['key' => $request->post('nickname')])) {
                $errors[] = 'A repository with this nickname already exists.';
            }
        }

        return $errors;
    }

    /**
     * @param string $nickname
     * @return array
     */
    private function validateNickname($nickname)
    {
        $errors = [];

        if (!$nickname) {
            $errors[] = 'Nickname is required';
        }

        if (!preg_match('@^[a-z0-9_-]*$@', strtolower($nickname))) {
            $errors[] = 'Nickname must be be composed of alphanumeric, underscore and/or hyphen characters';
        }

        if (mb_strlen($nickname, 'UTF-8') > 24) {
            $errors[] = 'Nickname must be under 24 characters';
        }

        if (mb_strlen($nickname, 'UTF-8') < 2) {
            $errors[] = 'Nickname must be more than 1 character';
        }

        return $errors;
    }

    /**
     * @param string $value
     * @param string $friendlyName
     * @param int $length
     * @param boolean $required
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
