<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Group;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Group;
use QL\Hal\Flasher;
use QL\Hal\Utility\ValidatorTrait;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;

class AddGroupController implements ControllerInterface
{
    use ValidatorTrait;

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type EntityRepository
     */
    private $groupRepo;

    /**
     * @type EntityManagerInterface
     */
    private $em;

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
     * @param Flasher $flasher
     * @param Request $request
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        Flasher $flasher,
        Request $request
    ) {
        $this->template = $template;

        $this->groupRepo = $em->getRepository(Group::CLASS);
        $this->em = $em;

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
                'identifier' => $this->request->post('identifier'),
                'name' => $this->request->post('name')
            ],
            'errors' => $this->checkFormErrors($this->request)
        ];

        if ($this->request->isPost()) {

            if (!$renderContext['errors']) {
                $group = $this->handleFormSubmission($this->request);

                $message = sprintf('Group "%s" added.', $group->name());
                return $this->flasher
                    ->withFlash($message, 'success')
                    ->load('repositories');
            }
        }

        $this->template->render($renderContext);
    }

    /**
     * @param Request $request
     * @return Group
     */
    private function handleFormSubmission(Request $request)
    {
        $identifier = strtolower($request->post('identifier'));
        $name = $request->post('name');

        $group = (new Group)
            ->withKey($identifier)
            ->withName($name);

        $this->em->persist($group);
        $this->em->flush();

        return $group;
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

        $identifier = strtolower($request->post('identifier'));
        $name = $request->post('name');

        $errors = $this->validateSimple($identifier, 'Identifier', 24, true);
        $errors = array_merge($errors, $this->validateText($name, 'Name', 48, true));

        if (!$errors && $group = $this->groupRepo->findOneBy(['key' => $identifier])) {
            $errors[] = 'A group with this identifier already exists.';
        }

        if (!$errors && $group = $this->groupRepo->findOneBy(['name' => $name])) {
            $errors[] = 'A group with this name already exists.';
        }

        return $errors;
    }
}
