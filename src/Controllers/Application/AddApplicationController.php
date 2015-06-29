<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Application;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Group;
use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Service\GitHubService;
use QL\Hal\Session;
use QL\Hal\Utility\ValidatorTrait;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;

class AddApplicationController implements ControllerInterface
{
    use ValidatorTrait;

    const SUCCESS = 'Application "%s" added.';
    const ERR_NO_GROUP = 'Please select a group.';

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
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param GitHubService $github
     * @param Session $session
     * @param UrlHelper $url
     * @param Request $request
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        GitHubService $github,
        Session $session,
        UrlHelper $url,
        Request $request
    ) {
        $this->template = $template;
        $this->groupRepo = $em->getRepository(Group::CLASS);
        $this->applicationRepo = $em->getRepository(Application::CLASS);
        $this->em = $em;
        $this->github = $github;
        $this->session = $session;
        $this->url = $url;

        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $orgs = $this->github->organizations();
        usort($orgs, function($a, $b) {
            return strcasecmp($a['login'], $b['login']);
        });

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
            'errors' => $this->checkFormErrors($this->request),
            'github_orgs' => $orgs
        ];

        if ($this->request->isPost()) {
            // this is kind of crummy
            if (!$renderContext['errors'] && !$group = $this->groupRepo->find($this->request->post('group'))) {
                $renderContext['errors'][] = self::ERR_NO_GROUP;
            }

            if (!$renderContext['errors']) {
                $application = $this->handleFormSubmission($this->request, $group);

                $message = sprintf(self::SUCCESS, $application->key());
                $this->session->flash($message, 'success');
                return $this->url->redirectFor('repositories');
            }
        }

        $this->template->render($renderContext);
    }

    /**
     * @param Request $request
     * @param Group $group
     *
     * @return Application
     */
    private function handleFormSubmission(Request $request, Group $group)
    {
        $identifier = strtolower($request->post('identifier'));
        $name = $request->post('name');
        $email = $request->post('notification_email');

        $user = strtolower($request->post('github_user'));
        $repo = strtolower($request->post('github_repo'));

        $application = (new Application)
            ->withKey($identifier)
            ->withName($name)
            ->withGroup($group)
            ->withEmail($email)

            ->withGithubOwner($user)
            ->withGithubRepo($repo)
            ->withEbName('');

        // Default to blank, not null
        $application->setBuildCmd('');
        $application->setBuildTransformCmd('');
        $application->setPrePushCmd('');
        $application->setPostPushCmd('');

        $this->em->persist($application);
        $this->em->flush();

        return $application;
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
            'github_user' => 'Github Organization',
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
        if (!$errors && $this->applicationRepo->findOneBy(['key' => $identifier])) {
            $errors[] = 'An application with this identifier already exists.';
        }

        return $errors;
    }

    /**
     * @param string $owner
     * @param string $repo
     *
     * @return array
     */
    private function validateGithubRepo($owner, $repo)
    {
        $errors = [];

        if (!$this->github->organization($owner)) {
            $errors[] = 'Invalid Github Enterprise organization';

        // elseif here so we dont bother making 2 github calls if the first one failed
        } elseif (!$this->github->repository($owner, $repo)) {
            $errors[] = 'Invalid Github Enterprise repository name';
        }

        return $errors;
    }
}
