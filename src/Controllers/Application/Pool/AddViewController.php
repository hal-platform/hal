<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Application\Pool;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\DeploymentView;
use QL\Hal\Flasher;
use QL\Hal\Utility\ValidatorTrait;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;

class AddViewController implements ControllerInterface
{
    use ValidatorTrait;

    const SUCCESS = 'View added successfully.';

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type Request
     */
    private $request;

    /**
     * @type Flasher
     */
    private $flasher;

    /**
     * @type callable
     */
    private $random;

    /**
     * @type EntityManagerInterface
     */
    private $em;

    /**
     * @type EntityRepository
     */
    private $viewRepo;

    /**
     * @type Application
     */
    private $application;

    /**
     * @type Environment
     */
    private $environment;

    /**
     * @type array
     */
    private $errors;

    /**
     * @param TemplateInterface $template
     * @param Request $request
     * @param Flasher $flasher
     * @param callable $random
     * @param EntityManagerInterface $em
     * @param Application $application
     * @param Environment $environment
     */
    public function __construct(
        TemplateInterface $template,
        Request $request,
        Flasher $flasher,
        callable $random,
        EntityManagerInterface $em,
        Application $application,
        Environment $environment
    ) {
        $this->template = $template;
        $this->request = $request;
        $this->flasher = $flasher;
        $this->random = $random;

        $this->em = $em;
        $this->viewRepo = $em->getRepository(DeploymentView::CLASS);

        $this->application = $application;
        $this->environment = $environment;

        $this->errors = [];
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $form = $this->data();

        if ($pool = $this->handleForm($form)) {
            return $this->flasher
                ->withFlash(self::SUCCESS, 'success')
                ->load('pools', ['application' => $this->application->id(), 'environment' => $this->environment->id()]);
        }

        $this->template->render([
            'form' => $form,
            'errors' => $this->errors,

            'application' => $this->application,
            'environment' => $this->environment
        ]);
    }

    /**
     * @param array $data
     *
     * @return DeploymentPool|null
     */
    private function handleForm(array $data)
    {
        if (!$this->request->isPost()) {
            return null;
        }

        $pool = $this->validateForm($data['name']);

        if ($pool) {
            // persist to database
            $this->em->persist($pool);
            $this->em->flush();
        }

        return $pool;
    }

    /**
     * @param string $name
     *
     * @return DeploymentPool|null
     */
    private function validateForm($name)
    {
        $this->errors = $this->validateText($name, 'Name', 100, true);

        if ($this->errors) return;

        $dupe = $this->viewRepo->findBy([
            'application' => $this->application,
            'environment' => $this->environment,
            'name' => $name
        ]);

        if ($dupe) {
            $this->errors[] = 'A Deployment view with this name already exists.';
        }

        if ($this->errors) return;

        $id = call_user_func($this->random);
        $pool = (new DeploymentView($id))
            ->withName($name)
            ->withApplication($this->application)
            ->withEnvironment($this->environment);

        return $pool;
    }

    /**
     * @return array
     */
    private function data()
    {
        $form = [
            'name' => trim($this->request->post('name')),
        ];

        return $form;
    }
}
