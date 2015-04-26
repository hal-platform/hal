<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller;

use Doctrine\ORM\EntityManager;
use MCP\DataType\GUID;
use MCP\DataType\HttpUrl;
use QL\Kraken\Entity\Environment;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\Url;
use QL\Hal\Session;
use Slim\Http\Request;

class EnvironmentsController implements ControllerInterface
{
    const SUCCESS = 'Environment "%s" added.';

    const ERR_INVALID_NAME = 'Invalid Name. Environment names must be at least two alphanumeric characters.';
    const ERR_INVALID_TOKEN = 'Invalid Token. Consul token must be at least two alphanumeric characters.';
    const ERR_INVALID_SERVER = 'Invalid Server.';

    const ERR_DUPLICATE_NAME = 'An environment with this name already exists.';

    const VALIDATE_NAME_REGEX = '/^[a-zA-Z0-9]{2,40}$/';
    const VALIDATE_TOKEN_REGEX = '/^[a-zA-Z0-9\-\=\.\+\/]{0,40}$/';

    /**
     * @type Request
     */
    private $request;

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type EntityManager
     */
    private $em;

    /**
     * @type EntityRepository
     */
    private $repository;

    /**
     * @type Url
     */
    private $url;

    /**
     * @type Session
     */
    private $session;

    /**
     * @type array
     */
    private $errors;

    /**
     * @param Request $request
     * @param TemplateInterface $template
     * @param Url $url
     * @param Session $session
     * @param $em
     */
    public function __construct(
        Request $request,
        TemplateInterface $template,
        $em,

        Url $url,
        Session $session
    ) {
        $this->request = $request;
        $this->template = $template;

        $this->em = $em;
        $this->repository = $this->em->getRepository(Environment::CLASS);

        $this->url = $url;
        $this->session = $session;

        $this->errors = [];
    }

    /**
     * @return null
     */
    public function __invoke()
    {
        $context = [];

        if ($this->request->isPost()) {
            $this->handleForm();
            $context = [
                'errors' => $this->errors,
                'form' => [
                    'name' => $this->request->post('name'),
                    'server' => $this->request->post('server'),
                    'token' => $this->request->post('token')
                ]
            ];
        }

        $envs = $this->repository->findBy([], ['id' => 'ASC']);

        $context['environments'] = $envs;

        $this->template->render($context);
    }

    /**
     * @return void
     */
    private function handleForm()
    {
        $name = $this->request->post('name');
        $server = $this->request->post('server');
        $token = $this->request->post('token');

        if (preg_match(self::VALIDATE_NAME_REGEX, $name) !== 1) {
            $this->errors[] = self::ERR_INVALID_NAME;
        }

        if (preg_match(self::VALIDATE_TOKEN_REGEX, $token) !== 1) {
            $this->errors[] = self::ERR_INVALID_TOKEN;
        }

        if (null === ($url = HttpUrl::create($server))) {
            $this->errors[] = self::ERR_INVALID_SERVER;
        }

        // dupe check
        if (!$this->errors && $dupe = $this->repository->findOneBy(['name' => $name])) {
            $this->errors[] = self::ERR_DUPLICATE_NAME;
        }

        if ($this->errors) {
            return null;
        }

        $this->saveEnvironment($name, $url->asString(), $token);
    }

    /**
     * @param string $name
     * @param string $server
     * @param string $token
     *
     * @return void
     */
    private function saveEnvironment($name, $server, $token)
    {
        $uniq = GUID::create()->asHex();
        $uniq = strtolower($uniq);

        $environment = (new Environment)
            ->withId($uniq)
            ->withName($name)
            ->withConsulServer($server)
            ->withConsulToken($token);

        // persist to database
        $this->em->persist($environment);
        $this->em->flush();

        // flash and redirect
        $this->session->flash(sprintf(self::SUCCESS, $name), 'success');
        $this->url->redirectFor('kraken.environments');
    }
}
