<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller\Application;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use MCP\DataType\GUID;
use QL\Kraken\Entity\Application;
use QL\Kraken\Entity\Encryption;
use QL\Kraken\Entity\Environment;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\Url;
use QL\Hal\Session;
use Slim\Http\Request;

class AddEnvironmentController implements ControllerInterface
{
    const SUCCESS = 'Environment added.';
    const ERR_INVALID_KEY = 'Invalid Key. Encryption Keys must be alphanumeric.';
    const ERR_DUPLICATE_ENV = 'This environment is already linked to this application.';
    const ERR_MISSING_ENV = 'Please select an environment.';

    const VALIDATE_KEY_REGEX = '/^[a-zA-Z0-9]{2,200}$/';

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
    private $envRepository;
    private $encRepository;

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
     * @param Application $application
     *
     * @param $em
     *
     * @param Url $url
     * @param Session $session
     */
    public function __construct(
        Request $request,
        TemplateInterface $template,
        Application $application,
        $em,
        Url $url,
        Session $session
    ) {
        $this->request = $request;
        $this->template = $template;
        $this->application = $application;

        $this->em = $em;
        $this->encRepository = $this->em->getRepository(Encryption::CLASS);
        $this->envRepository = $this->em->getRepository(Environment::CLASS);

        $this->url = $url;
        $this->session = $session;

        $this->errors = [];
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        if ($this->request->isPost()) {
            $this->handleForm();
        }

        $context = [
            'application' => $this->application,

            'environments' => $this->filterLinkedEnvironments(
                $this->encRepository->findBy(['application' => $this->application]),
                $this->envRepository->findBy([], ['name' => 'ASC'])
            ),

            'errors' => $this->errors,
            'form' => [
                'key' => $this->request->post('key'),
                'env' => $this->request->post('env')
            ]
        ];

        $this->template->render($context);
    }

    /**
     * @param Encryption[] $links
     * @param Environment[] $environments
     *
     * @return Environment[]
     */
    private function filterLinkedEnvironments($links, $environments)
    {
        $linked = [];
        foreach ($links as $link) {
            $linked[$link->environment()->id()] = true;
        }

        return array_filter($environments, function($env) use ($linked) {
            return !isset($linked[$env->id()]);
        });
    }

    /**
     * @return void
     */
    private function handleForm()
    {
        $key = $this->request->post('key');
        $envId = $this->request->post('env');

        if (preg_match(self::VALIDATE_KEY_REGEX, $key) !== 1) {
            $this->errors[] = self::ERR_INVALID_KEY;
        }

        if (!$envId) {
            $this->errors[] = self::ERR_MISSING_ENV;
        }

        if (!$this->errors) {
            if (!$env = $this->envRepository->find($envId)) {
                $this->errors[] = self::ERR_MISSING_ENV;
            }
        }

        // dupe check
        if (!$this->errors) {
            $dupe = $this->encRepository->findOneBy([
                'application' => $this->application,
                'environment' => $env
            ]);

            if ($dupe) {
                $this->errors[] = self::ERR_DUPLICATE_ENV;
            }
        }

        if ($this->errors) {
            return null;
        }

        $this->saveEncryption($env, $key);
    }

    /**
     * @param Environment $env
     * @param string $key
     *
     * @return void
     */
    private function saveEncryption(Environment $env, $key)
    {
        $uniq = GUID::create()->asHex();
        $uniq = strtolower($uniq);

        $encryption = (new Encryption)
            ->withId($uniq)
            ->withKey($key)
            ->withApplication($this->application)
            ->withEnvironment($env);

        // persist to database
        $this->em->persist($encryption);
        $this->em->flush();

        // flash and redirect
        $this->session->flash(self::SUCCESS, 'success');
        $this->url->redirectFor('kraken.application', ['id' => $this->application->id()]);
    }
}
