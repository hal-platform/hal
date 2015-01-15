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
use QL\Hal\Core\Entity\Type\ServerEnumType;
use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Session;
use QL\Hal\Slim\NotFound;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class EditServerController implements ControllerInterface
{
    const TYPE_EBS = 'elasticbeanstalk';
    const TYPE_RSYNC = 'rsync';

    const EBS_NAME = 'Elastic Beanstalk';

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
     * @param ServerRepository $serverRepo
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
        ServerRepository $serverRepo,
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
        $this->serverRepo = $serverRepo;
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
        if (!$server = $this->serverRepo->find($this->parameters['id'])) {
            return call_user_func($this->notFound);
        }

        $renderContext = [
            'form' => [
                'hostname' => ($this->request->isPost()) ? $this->request->post('hostname') : $server->getName(),
                'environment' => ($this->request->isPost()) ? $this->request->post('environment') : $server->getEnvironment()->getId(),
                'server_type' => ($this->request->isPost()) ? $this->request->post('server_type') : $server->getType(),
            ],
            'errors' => $this->checkFormErrors($this->request, $server),
            'server' => $server,
            'environments' => $this->envRepo->findBy([], ['order' => 'ASC'])
        ];

        if ($this->request->isPost()) {
            // this is kind of crummy
            if (!$renderContext['errors'] && !$environment = $this->envRepo->find($this->request->post('environment'))) {
                $renderContext['errors'][] = 'Please select an environment.';
            }

            if (!$renderContext['errors']) {
                $this->handleFormSubmission($this->request, $server, $environment);

                $this->session->flash('Server updated successfully.', 'success');
                return $this->url->redirectFor('server', ['id' => $server->getId()]);
            }
        }

        $rendered = $this->template->render($renderContext);
        $this->response->setBody($rendered);
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

        $type = $request->post('server_type');
        $name = strtolower($request->post('hostname'));

        if ($type === self::TYPE_EBS) {
            $name = self::EBS_NAME;
        }

        $server->setType($type);
        $server->setEnvironment($environment);
        $server->setName($name);

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
        $serverType = $request->post('server_type');

        $errors = [];

        if (!in_array($serverType, ServerEnumType::values())) {
            $errors[] = 'Please select a type.';
        }

        if (!$environmentId) {
            $errors[] = 'Please select an environment.';
        }

        // validate hostname if rsync server
        if ($serverType === self::TYPE_RSYNC && !$errors) {
            // normalize the hostname
            $hostname = strtolower($hostname);

            $errors = $this->validateHostname($hostname);

            // Only check duplicate hostname if it is being changed
            if (!$errors && $hostname != $server->getName()) {
                if ($server = $this->serverRepo->findOneBy(['name' => $hostname])) {
                    $errors[] = 'A server with this hostname already exists.';
                }
            }

        // validate servername for ebs server
        } elseif ($serverType === self::TYPE_EBS && !$errors) {

            // Only check duplicate EBS if it is being changed
            $hasChanged = ($environmentId != $server->getEnvironment()->getId() || $serverType != $server->getType());
            if (!$errors && $hasChanged) {
                if ($server = $this->serverRepo->findOneBy(['type' => self::TYPE_EBS, 'environment' => $environmentId])) {
                    $errors[] = 'An EBS server for this environment already exists.';
                }
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

        if (strlen($hostname) === 0) {
            $errors[] = 'You must enter a hostname';
        }

        $regex = '@^([0-9a-z]([0-9a-z-]*[0-9a-z])?)(\.[0-9a-z]([0-9a-z-]*[0-9a-z])?)*$@';
        if (!preg_match($regex, $hostname)) {
            $errors[] = 'Hostname must only use numbers, letters, hyphens and periods.';
        }

        if (strlen($hostname) > 24) {
            $errors[] = 'Hostname must be less than or equal to 32 characters';
        }

        return $errors;
    }
}
