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
use QL\Hal\Layout;
use QL\Hal\Session;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

class AdminAddController
{
    /**
     * @var Twig_Template
     */
    private $template;

    /**
     * @var Layout
     */
    private $layout;

    /**
     * @var GroupRepository
     */
    private $groupRepo;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var UrlHelper
     */
    private $url;

    /**
     * @param Twig_Template $template
     * @param Layout $layout
     * @param GroupRepository $groupRepo
     * @param EntityManager $entityManager
     * @param Session $session
     * @param UrlHelper $url
     */
    public function __construct(
        Twig_Template $template,
        Layout $layout,
        GroupRepository $groupRepo,
        EntityManager $entityManager,
        Session $session,
        UrlHelper $url
    ) {
        $this->template = $template;
        $this->layout = $layout;
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
        $renderContext = [
            'form' => [
                'nickname' => $request->post('nickname'),
                'name' => $request->post('name')
            ],
            'errors' => $this->checkFormErrors($request)
        ];

        if ($request->isPost()) {

            if (!$renderContext['errors']) {
                $group = $this->handleFormSubmission($request);

                $message = sprintf('Group "%s" added.', $group->getName());
                $this->session->addFlash($message, 'group-add');
                return $this->url->redirectFor('groups');
            }
        }

        $rendered = $this->layout->render($this->template, $renderContext);
        $response->body($rendered);
    }

    /**
     * @param Request $request
     * @return Group
     */
    private function handleFormSubmission(Request $request)
    {
        $nickname = $request->post('nickname');
        $name = $request->post('name');

        $group = new Group;
        $group->setKey($nickname);
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

        $nickname = $request->post('nickname');
        $name = $request->post('name');

        $errors = $this->validateNickname($nickname);
        $errors = array_merge($errors, $this->validateName($name));

        if (!$errors && $group = $this->groupRepo->findOneBy(['key' => $nickname])) {
            $errors[] = 'A group with this nickname already exists.';
        }

        if (!$errors && $group = $this->groupRepo->findOneBy(['name' => $name])) {
            $errors[] = 'A group with this name already exists.';
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

        if (mb_strlen($nickname, 'UTF-8')  > 24) {
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
