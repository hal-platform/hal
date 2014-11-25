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

class AdminAddController
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
     */
    public function __invoke(Request $request, Response $response)
    {
        $renderContext = [
            'form' => [
                'identifier' => $request->post('identifier'),
                'name' => $request->post('name')
            ],
            'errors' => $this->checkFormErrors($request)
        ];

        if ($request->isPost()) {

            if (!$renderContext['errors']) {
                $group = $this->handleFormSubmission($request);

                $message = sprintf('Group "%s" added.', $group->getName());
                $this->session->flash($message, 'success');

                return $this->url->redirectFor('groups');
            }
        }

        $rendered = $this->template->render($renderContext);
        $response->setBody($rendered);
    }

    /**
     * @param Request $request
     * @return Group
     */
    private function handleFormSubmission(Request $request)
    {
        $identifier = strtolower($request->post('identifier'));
        $name = $request->post('name');

        $group = new Group;
        $group->setKey($identifier);
        $group->setName($name);

        $this->entityManager->persist($group);
        $this->entityManager->flush();

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
