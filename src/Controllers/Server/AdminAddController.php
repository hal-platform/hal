<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Server;

use Doctrine\ORM\EntityManager;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\Server;
use QL\Hal\Core\Entity\Repository\EnvironmentRepository;
use QL\Hal\Core\Entity\Repository\ServerRepository;
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
     * @type ServerRepository
     */
    private $serverRepo;

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
     * @param ServerRepository $serverRepo
     * @param EnvironmentRepository $envRepo
     * @param EntityManager $entityManager
     * @param Session $session
     * @param UrlHelper $url
     */
    public function __construct(
        TemplateInterface $template,
        ServerRepository $serverRepo,
        EnvironmentRepository $envRepo,
        EntityManager $entityManager,
        Session $session,
        UrlHelper $url
    ) {
        $this->template = $template;
        $this->serverRepo = $serverRepo;
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
        if (!$environments = $this->envRepo->findBy([], ['order' => 'ASC'])) {
            $this->session->flash('A server requires an environment. Environments must be added before servers.', 'error');
            return $this->url->redirectFor('environment.admin.add');
        }

        $renderContext = [
            'form' => [
                'hostname' => $request->post('hostname'),
                'environment' => $request->post('environment')
            ],
            'errors' => $this->checkFormErrors($request),
            'environments' => $environments
        ];

        if ($request->isPost()) {
            // this is kind of crummy
            if (!$renderContext['errors'] && !$environment = $this->envRepo->find($request->post('environment'))) {
                $renderContext['errors'][] = 'Please select an environment.';
            }

            if (!$renderContext['errors']) {
                $server = $this->handleFormSubmission($request, $environment);

                $message = sprintf('Server "%s" added.', $server->getName());
                $this->session->flash($message, 'success');
                return $this->url->redirectFor('servers');
            }
        }

        $rendered = $this->template->render($renderContext);
        $response->setBody($rendered);
    }

    /**
     * @param Request $request
     * @param Environment $environment
     * @return Server
     */
    private function handleFormSubmission(Request $request, Environment $environment)
    {
        $hostname = strtolower($request->post('hostname'));

        $server = new Server;
        $server->setName($hostname);
        $server->setEnvironment($environment);

        $this->entityManager->persist($server);
        $this->entityManager->flush();

        return $server;
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

        $hostname = $request->post('hostname');
        $environmentId = $request->post('environment');

        // normalize the hostname
        $hostname = strtolower($hostname);

        $errors = $this->validateHostName($hostname);

        if (!$environmentId) {
            $errors[] = 'Please select an environment.';
        }

        if (!$errors && $server = $this->serverRepo->findOneBy(['name' => $hostname])) {
            $errors[] = 'A server with this hostname already exists.';
        }

        return $errors;
    }

    /**
     * Validates if a given string is a valid domain name according to RFC 1034
     *
     * The one exception to the spec is a domain name may start with a number.
     * In reality I know this is allowed, but I can't find any mention in any
     * other RFC.
     *
     * Additionally this validates the app specific length requirements.
     *
     * Examples:
     * - www.example.com - good
     * - .example.com - bad
     * - www..example.com - bad
     * - 1-800-flowers.com - good
     * - -awesome-.com - bad
     * - x---x.ql - good
     *
     * @param string $hostname
     * @return array
     */
    private function validateHostName($hostname)
    {
        $errors = [];

        $regex = '@^([0-9a-z]([0-9a-z-]*[0-9a-z])?)(\.[0-9a-z]([0-9a-z-]*[0-9a-z])?)*$@';
        if (!preg_match($regex, $hostname)) {
            $errors[] = 'Hostname must only use numbers, letters, hyphens and periods.';
        }

        if (strlen($hostname) > 24) {
            $errors[] = 'Hostname must be less than or equal to 32 characters';
        }

        if (strlen($hostname) === 0) {
            $errors[] = 'You must enter a hostname';
        }

        return $errors;
    }
}
