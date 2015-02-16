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
use QL\Hal\Helpers\ValidatorHelperTrait;
use QL\Hal\Services\GithubService;
use QL\Hal\Session;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class AddRepositoryController implements ControllerInterface
{
    use ValidatorHelperTrait;

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
     * @type Request
     */
    private $request;

    /**
     * @type Response
     */
    private $response;

    /**
     * @param TemplateInterface $template
     * @param GroupRepository $groupRepo
     * @param RepositoryRepository $repoRepo
     * @param EntityManager $entityManager
     * @param GithubService $github
     * @param Session $session
     * @param UrlHelper $url
     * @param Request $request
     * @param Response $response
     */
    public function __construct(
        TemplateInterface $template,
        GroupRepository $groupRepo,
        RepositoryRepository $repoRepo,
        EntityManager $entityManager,
        GithubService $github,
        Session $session,
        UrlHelper $url,
        Request $request,
        Response $response
    ) {
        $this->template = $template;
        $this->groupRepo = $groupRepo;
        $this->repoRepo = $repoRepo;
        $this->entityManager = $entityManager;
        $this->github = $github;
        $this->session = $session;
        $this->url = $url;

        $this->request = $request;
        $this->response = $response;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $renderContext = [
            'form' => [
                'identifier' => $this->request->post('identifier'),
                'name' => $this->request->post('name'),
                'group' => $this->request->post('group'),
                'github_user' => $this->request->post('github_user'),
                'github_repo' => $this->request->post('github_repo'),
                'notification_email' => $this->request->post('notification_email')
            ],
            'groups' => $this->groupRepo->findBy([], ['name' => 'ASC']),
            'errors' => $this->checkFormErrors($this->request)
        ];

        if ($this->request->isPost()) {
            // this is kind of crummy
            if (!$renderContext['errors'] && !$group = $this->groupRepo->find($this->request->post('group'))) {
                $renderContext['errors'][] = 'Please select a group.';
            }

            if (!$renderContext['errors']) {
                $repository = $this->handleFormSubmission($this->request, $group);

                $message = sprintf('Repository "%s" added.', $repository->getKey());
                $this->session->flash($message, 'success');
                return $this->url->redirectFor('repositories');
            }
        }

        $rendered = $this->template->render($renderContext);
        $this->response->setBody($rendered);
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

        $user = strtolower($request->post('github_user'));
        $repo = strtolower($request->post('github_repo'));

        $repository = new Repository;
        $repository->setKey($identifier);
        $repository->setName($name);
        $repository->setGroup($group);
        $repository->setEmail($email);

        $repository->setGithubUser($user);
        $repository->setGithubRepo($repo);

        // Default to blank, not null
        $repository->setBuildCmd('');
        $repository->setBuildTransformCmd('');
        $repository->setPrePushCmd('');
        $repository->setPostPushCmd('');
        $repository->setEbName('');

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
            'notification_email' => 'Notification Email'
        ];

        $identifier = strtolower($request->post('identifier'));

        $errors = array_merge(
            $this->validateSimple($identifier, $human['identifier'], 24, true),
            $this->validateText($request->post('name'), $human['name'], 64, true),

            $this->validateText($request->post('group'), $human['group'], 128, true),
            $this->validateText($request->post('github_user'), $human['github_user'], 48, true),
            $this->validateText($request->post('github_repo'), $human['github_repo'], 48, true),
            $this->validateText($request->post('notification_email'), $human['notification_email'], 128, false),

            $this->validateGithubRepo($request->post('github_user'), $request->post('github_repo'))
        );

        // check for duplicate nickname
        if (!$errors && $this->repoRepo->findOneBy(['key' => $identifier])) {
            $errors[] = 'A repository with this identifier already exists.';
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
}
