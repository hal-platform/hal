<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Server;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\Server;
use QL\Hal\Core\Repository\EnvironmentRepository;
use QL\Hal\Core\Type\EnumType\ServerEnum;
use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Session;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;

class AddServerController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type EntityRepository
     */
    private $serverRepo;

    /**
     * @type EnvironmentRepository
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
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param Session $session
     * @param UrlHelper $url
     * @param Request $request
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        Session $session,
        UrlHelper $url,
        Request $request
    ) {
        $this->template = $template;

        $this->serverRepo = $em->getRepository(Server::CLASS);
        $this->envRepo = $em->getRepository(Environment::CLASS);
        $this->em = $em;

        $this->session = $session;
        $this->url = $url;

        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        if (!$environments = $this->envRepo->getAllEnvironmentsSorted()) {
            $this->session->flash('A server requires an environment. Environments must be added before servers.', 'error');
            return $this->url->redirectFor('environment.admin.add');
        }

        $renderContext = [
            'form' => [
                'hostname' => $this->request->post('hostname'),
                'environment' => $this->request->post('environment'),
                'server_type' => $this->request->post('server_type'),
            ],
            'errors' => $this->checkFormErrors($this->request),
            'environments' => $environments
        ];

        if ($this->request->isPost()) {
            // this is kind of crummy
            if (!$renderContext['errors'] && !$environment = $this->envRepo->find($this->request->post('environment'))) {
                $renderContext['errors'][] = 'Please select an environment.';
            }

            if (!$renderContext['errors']) {
                $server = $this->handleFormSubmission($this->request, $environment);

                $name = $server->name();
                if ($server->type() === ServerEnum::TYPE_EB) {
                    $name = 'Elastic Beanstalk';
                } elseif ($server->type() === ServerEnum::TYPE_EC2) {
                    $name = 'EC2';
                }

                $message = sprintf('Server "%s" added.', $name);
                $this->session->flash($message, 'success');
                return $this->url->redirectFor('servers');
            }
        }

        $this->template->render($renderContext);
    }

    /**
     * @param Request $request
     * @param Environment $environment
     * @return Server
     */
    private function handleFormSubmission(Request $request, Environment $environment)
    {
        $type = $request->post('server_type');
        $name = strtolower(trim($request->post('hostname')));

        if ($type !== ServerEnum::TYPE_RSYNC) {
            $name = '';
        }

        $server = (new Server)
            ->withType($type)
            ->withEnvironment($environment)
            ->withName($name);

        $this->em->persist($server);
        $this->em->flush();

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

        $hostname = trim($request->post('hostname'));
        $environmentId = $request->post('environment');
        $serverType = $request->post('server_type');

        $errors = [];

        if (!in_array($serverType, ServerEnum::values())) {
            $errors[] = 'Please select a type.';
        }

        if (!$environmentId) {
            $errors[] = 'Please select an environment.';
        }

        // validate hostname if rsync server
        if ($serverType === ServerEnum::TYPE_RSYNC && !$errors) {
            // normalize the hostname
            $hostname = strtolower($hostname);

            $errors = $this->validateHostname($hostname);

            if ($server = $this->serverRepo->findOneBy(['name' => $hostname])) {
                $errors[] = 'A server with this hostname already exists.';
            }

        // validate duplicate EB for environment
        // Only 1 EB "server" per environment
        } elseif ($serverType === ServerEnum::TYPE_EB && !$errors) {
            if ($server = $this->serverRepo->findOneBy(['type' => ServerEnum::TYPE_EB, 'environment' => $environmentId])) {
                $errors[] = 'An EB server for this environment already exists.';
            }

        // validate duplicate EC2 for environment
        // Only 1 EC2 "server" per environment
        } elseif ($serverType === ServerEnum::TYPE_EC2 && !$errors) {
            if ($server = $this->serverRepo->findOneBy(['type' => ServerEnum::TYPE_EC2, 'environment' => $environmentId])) {
                $errors[] = 'An EC2 server for this environment already exists.';
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
    private function validateHostname($hostname)
    {
        $errors = [];

        if (strlen($hostname) === 0) {
            $errors[] = 'You must enter a hostname for internal servers.';
        }

        $regex = '@^([0-9a-z]([0-9a-z-]*[0-9a-z])?)(\.[0-9a-z]([0-9a-z-]*[0-9a-z])?)*$@';
        if (!preg_match($regex, $hostname)) {
            $errors[] = 'Hostname must only use numbers, letters, hyphens and periods.';
        }

        if (strlen($hostname) > 24) {
            $errors[] = 'Hostname must be less than or equal to 24 characters.';
        }

        return $errors;
    }
}
