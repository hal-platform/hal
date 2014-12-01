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
use QL\Hal\Session;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class AdminAddController
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type EnvironmentRepository
     */
    private $envRepo;

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
     * @param EnvironmentRepository $envRepo
     * @param EntityManager $entityManager
     * @param Session $session
     * @param UrlHelper $url
     */
    public function __construct(
        TemplateInterface $template,
        EnvironmentRepository $envRepo,
        EntityManager $entityManager,
        Session $session,
        UrlHelper $url
    ) {
        $this->template = $template;
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
            $this->session->flash($message, 'success');
            return $this->url->redirectFor('environments');
        }

        $rendered = $this->template->render($renderContext);
        $response->setBody($rendered);
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
