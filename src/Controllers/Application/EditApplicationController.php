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
use QL\Hal\Helpers\ValidatorHelperTrait;
use QL\Hal\Session;
use QL\Panthor\Slim\NotFound;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;

class EditApplicationController implements ControllerInterface
{
    use ValidatorHelperTrait;

    const ERR_DUPLICATE_IDENTIFIER = 'An application with this identifier already exists.';
    const SUCCESS = 'Application updated successfully.';

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
     * @param EntityManagerInterface $em
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
        EntityManagerInterface $em,
        Session $session,
        UrlHelper $url,
        Request $request,
        NotFound $notFound,
        array $parameters
    ) {
        $this->template = $template;

        $this->groupRepo = $em->getRepository(Group::CLASS);
        $this->applicationRepo = $em->getRepository(Application::CLASS);
        $this->em = $em;

        $this->session = $session;
        $this->url = $url;

        $this->request = $request;
        $this->notFound = $notFound;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        if (!$application = $this->applicationRepo->find($this->parameters['repository'])) {
            return call_user_func($this->notFound);
        }

        $renderContext = [
            'form' => [
                'identifier' => $this->request->post('identifier') ?: $application->key(),
                'name' => $this->request->post('name') ?: $application->name(),
                'group' => $this->request->post('group') ?: $application->group()->id(),
                'notification_email' => $this->request->post('notification_email') ?: $application->email(),
                'eb_name' => $this->request->post('eb_name') ?: $application->ebName()
            ],
            'application' => $application,
            'groups' => $this->groupRepo->findAll(),
            'errors' => $this->checkFormErrors($this->request, $application)
        ];

        if ($this->request->isPost()) {
            // this is kind of crummy
            if (!$renderContext['errors'] && !$group = $this->groupRepo->find($this->request->post('group'))) {
                $renderContext['errors'][] = 'Please select a group.';
            }

            if (!$renderContext['errors']) {
                $repository = $this->handleFormSubmission($this->request, $application, $group);

                $this->session->flash(self::SUCCESS, 'success');
                return $this->url->redirectFor('repository', ['id' => $application->id()]);
            }
        }

        $this->template->render($renderContext);
    }

    /**
     * @param Request $request
     * @param Application $application
     * @param Group $group
     *
     * @return Application
     */
    private function handleFormSubmission(Request $request, Application $application, Group $group)
    {
        $identifier = strtolower($request->post('identifier'));
        $name = $request->post('name');
        $email = $request->post('notification_email');
        $ebName = $request->post('eb_name');

        $application
            ->withKey($identifier)
            ->withName($name)
            ->withGroup($group)
            ->withEmail($email)
            ->withEbName($ebName);

        $this->em->merge($application);
        $this->em->flush();

        return $application;
    }

    /**
     * @param Request $request
     * @param Application $application
     *
     * @return array
     */
    private function checkFormErrors(Request $request, Application $application)
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
        if (!$errors && $identifier != $application->key()) {
            if ($repo = $this->applicationRepo->findOneBy(['key' => $identifier])) {
                $errors[] = self::ERR_DUPLICATE_IDENTIFIER;
            }
        }

        return $errors;
    }
}