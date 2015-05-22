<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller\Application;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Kraken\Entity\Application;
use QL\Kraken\Entity\Environment;
use QL\Kraken\Entity\Target;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Hal\Flasher;
use Slim\Http\Request;

class AddTargetController implements ControllerInterface
{
    const SUCCESS = 'Target added.';
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
     * @type EntityManagerInterface
     */
    private $em;

    /**
     * @type EntityRepository
     */
    private $envRepository;
    private $tarRepository;

    /**
     * @type callable
     */
    private $random;

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
     * @param Application $application
     *
     * @param EntityManagerInterface $em
     * @param Flasher $flasher
     * @param callable $random
     */
    public function __construct(
        Request $request,
        TemplateInterface $template,
        Application $application,
        EntityManagerInterface $em,
        Flasher $flasher,
        callable $random
    ) {
        $this->request = $request;
        $this->template = $template;
        $this->application = $application;

        $this->em = $em;
        $this->tarRepository = $this->em->getRepository(Target::CLASS);
        $this->envRepository = $this->em->getRepository(Environment::CLASS);

        $this->flasher = $flasher;
        $this->random = $random;

        $this->errors = [];
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        if ($this->request->isPost()) {
            if ($target = $this->handleForm()) {
                $this->flasher
                    ->withFlash(self::SUCCESS, 'success')
                    ->load('kraken.application', ['id' => $this->application->id()]);
            }
        }

        $environments = $this->filterTargets(
            $this->tarRepository->findBy(['application' => $this->application]),
            $this->envRepository->findBy([], ['name' => 'ASC'])
        );

        if (!$environments) {
            $this->flasher
                ->withFlash('No environments found.')
                ->load('kraken.application', ['id' => $this->application->id()]);
        }

        $context = [
            'application' => $this->application,

            'environments' => $environments,

            'errors' => $this->errors,
            'form' => [
                'key' => $this->request->post('key'),
                'env' => $this->request->post('env')
            ]
        ];

        $this->template->render($context);
    }

    /**
     * @param Target[] $targets
     * @param Environment[] $environments
     *
     * @return Environment[]
     */
    private function filterTargets($targets, $environments)
    {
        $linked = [];
        foreach ($targets as $target) {
            $linked[$target->environment()->id()] = true;
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
            $dupe = $this->tarRepository->findOneBy([
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

        return $this->saveTarget($env, $key);
    }

    /**
     * @param Environment $env
     * @param string $key
     *
     * @return Target
     */
    private function saveTarget(Environment $env, $key)
    {
        $id = call_user_func($this->random);

        $target = (new Target)
            ->withId($uniq)
            ->withKey($key)
            ->withApplication($this->application)
            ->withEnvironment($env);

        // persist to database
        $this->em->persist($target);
        $this->em->flush();

        return $target;
    }
}
