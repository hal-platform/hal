<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Group;

use Doctrine\ORM\EntityManager;
use QL\Hal\Core\Entity\Group;
use QL\Hal\Core\Entity\Repository\GroupRepository;
use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Helpers\ValidatorHelperTrait;
use QL\Hal\Session;
use QL\Hal\Slim\NotFound;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class EditGroupController implements ControllerInterface
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
     * @param GroupRepository $groupRepo
     * @param EntityManager $entityManager
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
        if (!$group = $this->groupRepo->find($this->parameters['id'])) {
            return call_user_func($this->notFound);
        }

        $context = [
            'form' => [
                'identifier' => ($this->request->isPost()) ? $this->request->post('identifier') : $group->getKey(),
                'name' => ($this->request->isPost()) ? $this->request->post('name') : $group->getName()
            ],
            'group' => $group,
            'errors' => $this->checkFormErrors($this->request, $group)
        ];

        if ($this->request->isPost()) {

            if (!$context['errors']) {
                $group = $this->handleFormSubmission($this->request, $group);

                $this->session->flash('Group updated successfully.', 'success');
                return $this->url->redirectFor('group', ['id' => $group->getId()]);
            }
        }

        $rendered = $this->template->render($context);
        $this->response->setBody($rendered);
    }

    /**
     * @param Request $request
     * @param Group $group
     * @return Group
     */
    private function handleFormSubmission(Request $request, Group $group)
    {
        $identifier = strtolower($request->post('identifier'));
        $name = $request->post('name');

        $group->setKey($identifier);
        $group->setName($name);

        $this->entityManager->merge($group);
        $this->entityManager->flush();

        return $group;
    }

    /**
     * @param Request $request
     * @param Group $group
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
        if (!$errors && $identifier !== $group->getKey()) {
            if ($dupeGroup = $this->groupRepo->findOneBy(['key' => $identifier])) {
                $errors[] = 'A group with this nickname already exists.';
            }
        }

        // Only check duplicate name if it is being changed
        if (!$errors && $name !== $group->getName()) {
            if ($dupeGroup = $this->groupRepo->findOneBy(['name' => $name])) {
                $errors[] = 'A group with this name already exists.';
            }
        }

        return $errors;
    }
}
