<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Environment;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Session;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class AddEnvironmentController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type EntityRepository
     */
    private $envRepo;

    /**
     * @type EntityManagerInterface
     */
    private $em;

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
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param Session $session
     * @param UrlHelper $url
     * @param Request $request
     * @param Response $response
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        Session $session,
        UrlHelper $url,
        Request $request,
        Response $response
    ) {
        $this->template = $template;

        $this->envRepo = $em->getRepository(Environment::CLASS);
        $this->em = $em;

        $this->session = $session;
        $this->url = $url;

        $this->request = $request;
        $this->response = $response;
    }


    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $renderContext = [
            'form' => [
                'name' => $this->request->post('name')
            ],
            'errors' => $this->checkFormErrors($this->request)
        ];

        if ($this->handleFormSubmission($this->request, $renderContext['errors'])) {
            $message = sprintf('Environment "%s" added.', $this->request->post('name'));
            $this->session->flash($message, 'success');
            return $this->url->redirectFor('environments');
        }

        $rendered = $this->template->render($renderContext);
        $this->response->setBody($rendered);
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

        $this->em->persist($environment);
        $this->em->flush();

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
