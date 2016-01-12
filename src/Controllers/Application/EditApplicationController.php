<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Controllers\Application;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Group;
use QL\Hal\Flasher;
use QL\Hal\Utility\ValidatorTrait;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;

class EditApplicationController implements ControllerInterface
{
    use ValidatorTrait;

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
     * @type Application
     */
    private $application;

    /**
     * @type Flasher
     */
    private $flasher;

    /**
     * @type Request
     */
    private $request;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param Application $application
     * @param Flasher $flasher
     * @param Request $request
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        Application $application,
        Flasher $flasher,
        Request $request
    ) {
        $this->template = $template;

        $this->groupRepo = $em->getRepository(Group::CLASS);
        $this->applicationRepo = $em->getRepository(Application::CLASS);
        $this->em = $em;

        $this->application = $application;
        $this->flasher = $flasher;

        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $renderContext = [
            'form' => [
                'identifier' => $this->request->post('identifier') ?: $this->application->key(),
                'name' => $this->request->post('name') ?: $this->application->name(),
                'group' => $this->request->post('group') ?: $this->application->group()->id(),
                'notification_email' => $this->request->post('notification_email') ?: $this->application->email()
            ],
            'application' => $this->application,
            'groups' => $this->groupRepo->findAll(),
            'errors' => $this->checkFormErrors($this->request, $this->application)
        ];

        if ($this->request->isPost()) {
            // this is kind of crummy
            if (!$renderContext['errors'] && !$group = $this->groupRepo->find($this->request->post('group'))) {
                $renderContext['errors'][] = 'Please select a group.';
            }

            if (!$renderContext['errors']) {
                $application = $this->handleFormSubmission($this->request, $this->application, $group);

                return $this->flasher
                    ->withFlash(self::SUCCESS, 'success')
                    ->load('application', ['application' => $this->application->id()]);
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

        $application
            ->withKey($identifier)
            ->withName($name)
            ->withGroup($group)
            ->withEmail($email);

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
            'notification_email' => 'Notification Email'
        ];

        $identifier = strtolower($request->post('identifier'));

        $errors = array_merge(
            $this->validateSimple($identifier, $human['identifier'], 24, true),
            $this->validateText($request->post('name'), $human['name'], 64, true),

            $this->validateText($request->post('group'), $human['group'], 128, true),
            $this->validateText($request->post('notification_email'), $human['notification_email'], 128, false)
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
