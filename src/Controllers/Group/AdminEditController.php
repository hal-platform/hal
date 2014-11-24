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
use QL\Hal\Session;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class AdminEditController
{
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
                'nickname' => ($request->isPost()) ? $request->post('nickname') : $group->getKey(),
                'name' => ($request->isPost()) ? $request->post('name') : $group->getName()
            ],
            'group' => $group,
            'errors' => $this->checkFormErrors($request, $group)
        ];

        if ($request->isPost()) {

            if (!$renderContext['errors']) {
                $group = $this->handleFormSubmission($request, $group);

                $this->session->addFlash('Group updated successfully.', 'group-edit');
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
        $nickname = $request->post('nickname');
        $name = $request->post('name');

        $group->setKey($nickname);
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

        $nickname = $request->post('nickname');
        $name = $request->post('name');

        $errors = $this->validateNickname($nickname);
        $errors = array_merge($errors, $this->validateName($name));

        // Only check duplicate nickname if it is being changed
        if (!$errors && $nickname != $group->getKey()) {
            if ($dupeGroup = $this->groupRepo->findOneBy(['key' => $nickname])) {
                $errors[] = 'A group with this nickname already exists.';
            }
        }

        // Only check duplicate name if it is being changed
        if (!$errors && $name != $group->getName()) {
            if ($dupeGroup = $this->groupRepo->findOneBy(['name' => $name])) {
                $errors[] = 'A group with this name already exists.';
            }
        }

        return $errors;
    }

    /**
     * @param string $nickname
     * @return array
     */
    private function validateNickname($nickname)
    {
        $errors = [];

        if (!$nickname) {
            $errors[] = 'Nickname must be specified';
        }

        if (!preg_match('@^[a-z0-9_-]*$@', strtolower($nickname))) {
            $errors[] = 'Nickname must be be composed of alphanumeric, underscore and/or hyphen characters';
        }

        if ($nickname > 24) {
            $errors[] = 'Nickname must be under 24 characters';
        }

        return $errors;
    }

    /**
     * @param string $name
     * @return array
     */
    private function validateName($name)
    {
        $errors = [];

        if (!$name) {
            $errors[] = 'Name must be specified';
        }

        if (!mb_check_encoding($name, 'UTF-8')) {
            $errors[] = 'Name must be valid UTF-8';
        }

        if (mb_strlen($name, 'UTF-8') > 48) {
            $errors[] = 'Name must be 48 characters or under';
        }

        return $errors;
    }
}
