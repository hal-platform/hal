<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Kraken\Controller\Application;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Kraken\Core\Entity\Application;
use QL\Kraken\Core\Entity\Environment;
use QL\Kraken\Core\Entity\Target;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Hal\Flasher;
use Slim\Http\Request;

class AddTargetController implements ControllerInterface
{
    const SUCCESS = 'Target added.';
    const ERR_INVALID_KEY = 'Encryption Key must be 6 alphanumeric characters.';
    const ERR_DUPLICATE_ENV = 'This environment is already linked to this application.';
    const ERR_MISSING_ENV = 'Please select an environment.';

    const VALIDATE_QKS_KEY_REGEX = '/^[0-9A-Z]{6}$/';

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
    private $environmentRepo;
    private $targetRepo;

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
        $this->targetRepo = $this->em->getRepository(Target::CLASS);
        $this->environmentRepo = $this->em->getRepository(Environment::CLASS);

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
                    ->load('kraken.application', ['application' => $this->application->id()]);
            }
        }

        $environments = $this->filterTargets(
            $this->targetRepo->findBy(['application' => $this->application]),
            $this->environmentRepo->findBy([], ['name' => 'ASC'])
        );

        if (!$environments) {
            $this->flasher
                ->withFlash('No environments found.')
                ->load('kraken.application', ['application' => $this->application->id()]);
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

        if (preg_match(self::VALIDATE_QKS_KEY_REGEX, $key) !== 1) {
            $this->errors[] = self::ERR_INVALID_KEY;
        }

        if (!$envId) {
            $this->errors[] = self::ERR_MISSING_ENV;
        }

        if (!$this->errors) {
            if (!$env = $this->environmentRepo->find($envId)) {
                $this->errors[] = self::ERR_MISSING_ENV;
            }
        }

        // dupe check
        if (!$this->errors) {
            $dupe = $this->targetRepo->findOneBy([
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

        $target = (new Target($id))
            ->withKey($key)
            ->withApplication($this->application)
            ->withEnvironment($env);

        // persist to database
        $this->em->persist($target);
        $this->em->flush();

        return $target;
    }
}
