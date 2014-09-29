<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Environment;

use Doctrine\ORM\EntityManager;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\Repository\EnvironmentRepository;
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
     * @var EnvironmentRepository
     */
    private $envRepo;

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
     * @param EnvironmentRepository $envRepo
     * @param EntityManager $entityManager
     * @param Session $session
     * @param UrlHelper $url
     */
    public function __construct(
        Twig_Template $template,
        Layout $layout,
        EnvironmentRepository $envRepo,
        EntityManager $entityManager,
        Session $session,
        UrlHelper $url
    ) {
        $this->template = $template;
        $this->layout = $layout;
        $this->envRepo = $envRepo;
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
                'name' => $request->post('name')
            ],
            'errors' => $this->checkFormErrors($request)
        ];

        if ($this->handleFormSubmission($request, $renderContext['errors'])) {
            $message = sprintf('Environment "%s" added.', $request->post('name'));
            $this->session->addFlash($message, 'environment-add');
            return $this->url->redirectFor('environments');
        }

        $rendered = $this->layout->render($this->template, $renderContext);
        $response->body($rendered);
    }

    /**
     * Returns true if the form was submitted successfully.
     *
     * @param Request $request
     * @param array $errors
     * @return null
     */
    private function handleFormSubmission(Request $request, array $errors)
    {
        if (!$request->isPost() || $errors) {
            return false;
        }

        $nextOrder = 1;
        if ($maxEnvironment = $this->envRepo->findBy([], ['order' => 'DESC'], 1)) {
            $maxEnvironment = array_pop($maxEnvironment);
            $nextOrder = $maxEnvironment->getOrder() + 1;
        }

        $environment = new Environment;
        $environment->setKey($request->post('name'));
        $environment->setOrder($nextOrder);

        $this->entityManager->persist($environment);
        $this->entityManager->flush();

        return true;
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

        $errors = [];
        $name = $request->post('name');

        if (!preg_match('@^[a-zA-Z_-]*$@', $name)) {
            $errors[] = 'Environment name must consist of letters, underscores and/or hyphens.';
        }

        if (mb_strlen($name, 'UTF-8') > 24 || mb_strlen($name, 'UTF-8') < 2) {
            $errors[] = 'Environment name must be between 2 and 24 characters.';
        }

        if (!$errors && $env = $this->envRepo->findOneBy(['key' => $name])) {
            $errors[] = 'An environment with this name already exists.';
        }

        return $errors;
    }
}
