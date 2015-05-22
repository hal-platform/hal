<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use MCP\DataType\HttpUrl;
use QL\Kraken\Entity\Environment;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Hal\Flasher;
use Slim\Http\Request;

class AddEnvironmentController implements ControllerInterface
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
     * @type EntityManagerInterface
     */
    private $environmentRepo;

    /**
     * @type Flasher
     */
    private $flasher;

    /**
     * @type array
     */
    private $errors;

    /**
     * @param Request $request
     * @param TemplateInterface $template
     * @param Flasher $flasher
     * @param EntityManagerInterface $em
     * @param EntityRepository $halRepo
     * @param callable $random
     */
    public function __construct(
        Request $request,
        TemplateInterface $template,
        Flasher $flasher,
        EntityManagerInterface $em,
        callable $random
    ) {
        $this->request = $request;
        $this->template = $template;
        $this->flasher = $flasher;
        $this->random = $random;

        $this->em = $em;
        $this->environmentRepo = $this->em->getRepository(Environment::CLASS);

        $this->errors = [];
    }

    /**
     * @return null
     */
    public function __invoke()
    {
        $context = [];

        if ($this->request->isPost() && $environment = $this->handleForm()) {
            return $this->flasher
                ->withFlash(sprintf(self::SUCCESS, $environment->name()), 'success')
                ->load('kraken.environments');
        }

        $context = [
            'errors' => $this->errors,
            'form' => [
                'name' => $this->request->post('name'),
                'server' => $this->request->post('server'),
                'token' => $this->request->post('token')
            ]
        ];

        $this->template->render($context);
    }

    /**
     * @return Environment|null
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
        if (!$this->errors && $dupe = $this->environmentRepo->findOneBy(['name' => $name])) {
            $this->errors[] = self::ERR_DUPLICATE_NAME;
        }

        if ($this->errors) {
            return null;
        }

        return $this->saveEnvironment($name, $url->asString(), $token);
    }

    /**
     * @param string $name
     * @param string $server
     * @param string $token
     *
     * @return Environment
     */
    private function saveEnvironment($name, $server, $token)
    {
        $id = call_user_func($this->random);

        $environment = (new Environment)
            ->withId($id)
            ->withName($name)
            ->withConsulServer($server)
            ->withConsulToken($token);

        // persist to database
        $this->em->persist($environment);
        $this->em->flush();

        return $environment;
    }
}
