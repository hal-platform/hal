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
use QL\Hal\Layout;
use QL\Hal\Session;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

class AdminEditController
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
     * @var ServerRepository
     */
    private $serverRepo;

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
     * @param ServerRepository $serverRepo
     * @param EnvironmentRepository $envRepo
     * @param EntityManager $entityManager
     * @param Session $session
     * @param UrlHelper $url
     */
    public function __construct(
        Twig_Template $template,
        Layout $layout,
        ServerRepository $serverRepo,
        EnvironmentRepository $envRepo,
        EntityManager $entityManager,
        Session $session,
        UrlHelper $url
    ) {
        $this->template = $template;
        $this->layout = $layout;
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
        if (!$server = $this->serverRepo->find($params['id'])) {
            return $notFound();
        }

        $renderContext = [
            'form' => [
                'hostname' => ($request->isPost()) ? $request->post('hostname') : $server->getName(),
                'environment' => ($request->isPost()) ? $request->post('environment') : $server->getEnvironment()->getId()
            ],
            'errors' => $this->checkFormErrors($request, $server),
            'server' => $server,
            'environments' => $this->envRepo->findBy([], ['order' => 'ASC'])
        ];

        if ($request->isPost()) {
            // this is kind of crummy
            if (!$renderContext['errors'] && !$environment = $this->envRepo->find($request->post('environment'))) {
                $renderContext['errors'][] = 'Please select an environment.';
            }

            if (!$renderContext['errors']) {
                $this->handleFormSubmission($request, $server, $environment);

                $this->session->addFlash('Server updated successfully.', 'server-edit');
                return $this->url->redirectFor('server', ['id' => $server->getId()]);
            }
        }

        $rendered = $this->layout->render($this->template, $renderContext);
        $response->body($rendered);
    }

    /**
     * @param Request $request
     * @param Server $server
     * @param Environment $environment
     * @return Server
     */
    private function handleFormSubmission(Request $request, Server $server, Environment $environment)
    {
        $hostname = strtolower($request->post('hostname'));

        $server->setName($hostname);
        $server->setEnvironment($environment);

        $this->entityManager->merge($server);
        $this->entityManager->flush();

        return $server;
    }

    /**
     * @param Request $request
     * @param Server $server
     * @return array
     */
    private function checkFormErrors(Request $request, Server $server)
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

        // Only check duplicate hostname if it is being changed
        if (!$errors && $hostname != $server->getName()) {
            if ($server = $this->serverRepo->findOneBy(['name' => $hostname])) {
                $errors[] = 'A server with this hostname already exists.';
            }
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
