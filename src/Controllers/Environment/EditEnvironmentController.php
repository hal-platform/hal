<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Environment;

use Doctrine\ORM\EntityManager;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Repository\EnvironmentRepository;
use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Session;
use QL\Hal\Slim\NotFound;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class EditEnvironmentController implements ControllerInterface
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
     * @param EnvironmentRepository $envRepo
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
        EnvironmentRepository $envRepo,
        EntityManager $entityManager,
        Session $session,
        UrlHelper $url,
        Request $request,
        Response $response,
        NotFound $notFound,
        array $parameters
    ) {
        $this->template = $template;
        $this->envRepo = $envRepo;
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
        if (!$environment = $this->envRepo->find($this->parameters['id'])) {
            return call_user_func($this->notFound);
        }

        $renderContext = [
            'form' => [
                'name' => ($this->request->isPost()) ? $this->request->post('name') : $environment->getKey()
            ],
            'env' => $environment,
            'errors' => $this->checkFormErrors($this->request)
        ];

        if ($this->handleFormSubmission($this->request, $environment, $renderContext['errors'])) {
            $this->session->flash('Environment updated successfully.', 'success');
            return $this->url->redirectFor('environment', ['id' => $environment->getId()]);
        }

        $rendered = $this->template->render($renderContext);
        $this->response->setBody($rendered);
    }

    /**
     * Returns true if the form was submitted successfully.
     *
     * @param Request $request
     * @param Environment $environment
     * @param array $errors
     * @return null
     */
    private function handleFormSubmission(Request $request, Environment $environment, array $errors)
    {
        if (!$request->isPost() || $errors) {
            return false;
        }

        $environment->setKey($request->post('name'));
        $this->entityManager->merge($environment);
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

        if (strlen($name) > 24 || strlen($name) < 2) {
            $errors[] = 'Environment name must be between 2 and 24 characters.';
        }

        if (!$errors && $env = $this->envRepo->findOneBy(['key' => $name])) {
            $errors[] = 'An environment with this name already exists.';
        }

        return $errors;
    }
}
