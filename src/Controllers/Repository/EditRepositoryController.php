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
use QL\Hal\Core\Repository\GroupRepository;
use QL\Hal\Core\Repository\RepositoryRepository;
use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Helpers\ValidatorHelperTrait;
use QL\Hal\Session;
use QL\Hal\Slim\NotFound;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class EditRepositoryController implements ControllerInterface
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
     * @type NotFound
     */
    private $notFound;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param TemplateInterface $template
     * @param Layout $layout
     * @param GroupRepository $groupRepo
     * @param RepositoryRepository $repoRepo
     * @param EntityManager $entityManager
     * @param GithubService $github
     * @param Session $session
     * @param UrlHelper $url
     * @param Request $request
     * @param Response $response
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        TemplateInterface $template,
        GroupRepository $groupRepo,
        RepositoryRepository $repoRepo,
        EntityManager $entityManager,
        Session $session,
        UrlHelper $url,
        Request $request,
        Response $response,
        NotFound $notFound,
        array $parameters
    ) {
        $this->template = $template;
        $this->groupRepo = $groupRepo;
        $this->repoRepo = $repoRepo;
        $this->entityManager = $entityManager;
        $this->session = $session;
        $this->url = $url;

        $this->request = $request;
        $this->response = $response;
        $this->notFound = $notFound;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        if (!$repo = $this->repoRepo->find($this->parameters['repository'])) {
            return call_user_func($this->notFound);
        }

        $renderContext = [
            'form' => [
                'identifier' => $this->request->post('identifier') ?: $repo->getKey(),
                'name' => $this->request->post('name') ?: $repo->getName(),
                'group' => $this->request->post('group') ?: $repo->getGroup()->getId(),
                'notification_email' => $this->request->post('notification_email') ?: $repo->getEmail(),
                'eb_name' => $this->request->post('eb_name') ?: $repo->getEbName()
            ],
            'repository' => $repo,
            'groups' => $this->groupRepo->findAll(),
            'errors' => $this->checkFormErrors($this->request, $repo)
        ];

        if ($this->request->isPost()) {
            // this is kind of crummy
            if (!$renderContext['errors'] && !$group = $this->groupRepo->find($this->request->post('group'))) {
                $renderContext['errors'][] = 'Please select a group.';
            }

            if (!$renderContext['errors']) {
                $repository = $this->handleFormSubmission($this->request, $repo, $group);

                $this->session->flash('Repository updated successfully.', 'success');
                return $this->url->redirectFor('repository', ['id' => $repo->getId()]);
            }
        }

        $rendered = $this->template->render($renderContext);
        $this->response->setBody($rendered);
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
        $ebName = $request->post('eb_name');

        $repository->setKey($identifier);
        $repository->setName($name);
        $repository->setGroup($group);
        $repository->setEmail($email);
        $repository->setEbName($ebName);

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
            'eb_name' => 'Elastic Beanstalk Application Name',
        ];

        $identifier = strtolower($request->post('identifier'));

        $errors = array_merge(
            $this->validateSimple($identifier, $human['identifier'], 24, true),
            $this->validateText($request->post('name'), $human['name'], 64, true),

            $this->validateText($request->post('group'), $human['group'], 128, true),
            $this->validateText($request->post('notification_email'), $human['notification_email'], 128, false),
            $this->validateText($request->post('eb_name'), $human['eb_name'], 255, false)
        );

        // Only check for duplicate identifier if it is being changed
        if (!$errors && $identifier != $repository->getKey()) {
            if ($repo = $this->repoRepo->findOneBy(['key' => $identifier])) {
                $errors[] = 'A repository with this identifier already exists.';
            }
        }

        return $errors;
    }
}
