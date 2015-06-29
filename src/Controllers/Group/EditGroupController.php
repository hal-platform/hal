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
use QL\Panthor\Slim\NotFound;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;

class EditGroupController implements ControllerInterface
{
    use ValidatorTrait;

    const SUCCESS = 'Group updated successfully.';

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
     * @type NotFound
     */
    private $notFound;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param Flasher $flasher
     * @param Request $request
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        Flasher $flasher,
        Request $request,
        NotFound $notFound,
        array $parameters
    ) {
        $this->template = $template;

        $this->groupRepo = $em->getRepository(Group::CLASS);
        $this->em = $em;

        $this->flasher = $flasher;
        $this->request = $request;
        $this->notFound = $notFound;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        if (!$group = $this->groupRepo->find($this->parameters['id'])) {
            return call_user_func($this->notFound);
        }

        $context = [
            'form' => [
                'identifier' => ($this->request->isPost()) ? $this->request->post('identifier') : $group->key(),
                'name' => ($this->request->isPost()) ? $this->request->post('name') : $group->name()
            ],
            'group' => $group,
            'errors' => $this->checkFormErrors($this->request, $group)
        ];

        if ($this->request->isPost()) {

            if (!$context['errors']) {
                $group = $this->handleFormSubmission($this->request, $group);

                return $this->flasher
                    ->withFlash(self::SUCCESS, 'success')
                    ->load('group', ['id' => $group->id()]);
            }
        }

        $this->template->render($context);
    }

    /**
     * @param Request $request
     * @param Group $group
     *
     * @return Group
     */
    private function handleFormSubmission(Request $request, Group $group)
    {
        $identifier = strtolower($request->post('identifier'));
        $name = $request->post('name');

        $group
            ->withKey($identifier)
            ->withName($name);

        $this->em->merge($group);
        $this->em->flush();

        return $group;
    }

    /**
     * @param Request $request
     * @param Group $group
     *
     * @return array
     */
    private function checkFormErrors(Request $request, Group $group)
    {
        if (!$request->isPost()) {
            return [];
        }

        $identifier = strtolower($request->post('identifier'));
        $name = $request->post('name');

        $errors = $this->validateSimple($identifier, 'Identifier', 24, true);
        $errors = array_merge($errors, $this->validateText($name, 'Name', 48, true));

        // Only check duplicate nickname if it is being changed
        if (!$errors && $identifier !== $group->key()) {
            if ($dupeGroup = $this->groupRepo->findOneBy(['key' => $identifier])) {
                $errors[] = 'A group with this nickname already exists.';
            }
        }

        // Only check duplicate name if it is being changed
        if (!$errors && $name !== $group->name()) {
            if ($dupeGroup = $this->groupRepo->findOneBy(['name' => $name])) {
                $errors[] = 'A group with this name already exists.';
            }
        }

        return $errors;
    }
}
