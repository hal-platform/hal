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
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class AdminEditController
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
     * @param TemplateInterface $template
     * @param GroupRepository $groupRepo
     * @param EntityManager $entityManager
     * @param Session $session
     * @param UrlHelper $url
     */
    public function __construct(
        TemplateInterface $template,
        GroupRepository $groupRepo,
        EntityManager $entityManager,
        Session $session,
        UrlHelper $url
    ) {
        $this->template = $template;
        $this->groupRepo = $groupRepo;
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
        if (!$group = $this->groupRepo->find($params['id'])) {
            return $notFound();
        }

        $renderContext = [
            'form' => [
                'identifier' => ($request->isPost()) ? $request->post('identifier') : $group->getKey(),
                'name' => ($request->isPost()) ? $request->post('name') : $group->getName()
            ],
            'group' => $group,
            'errors' => $this->checkFormErrors($request, $group)
        ];

        if ($request->isPost()) {

            if (!$renderContext['errors']) {
                $group = $this->handleFormSubmission($request, $group);

                $this->session->flash('Group updated successfully.', 'success');
                return $this->url->redirectFor('group', ['id' => $group->getId()]);
            }
        }

        $rendered = $this->template->render($renderContext);
        $response->setBody($rendered);
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
