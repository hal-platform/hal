<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Kraken\Controller\Application;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Application as HalApplication;
use QL\Hal\Flasher;
use QL\Kraken\Core\Entity\Application;
use QL\Kraken\Validator\ApplicationValidator;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;

class EditApplicationController implements ControllerInterface
{
    const SUCCESS = 'Application updated.';

    /**
     * @var Request
     */
    private $request;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var Application
     */
    private $application;

    /**
     * @var Flasher
     */
    private $flasher;

    /**
     * @var ApplicationValidator
     */
    private $validator;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var EntityRepository
     */
    private $environmentRepo;
    private $halRepo;

    /**
     * @param Request $request
     * @param TemplateInterface $template
     * @param Application $application
     * @param Flasher $flasher
     * @param ApplicationValidator $validator
     * @param EntityManagerInterface $em
     */
    public function __construct(
        Request $request,
        TemplateInterface $template,
        Application $application,
        Flasher $flasher,
        ApplicationValidator $validator,
        EntityManagerInterface $em
    ) {
        $this->request = $request;
        $this->template = $template;
        $this->application = $application;
        $this->flasher = $flasher;
        $this->validator = $validator;

        $this->em = $em;
        $this->applicationRepo = $em->getRepository(Application::CLASS);
        $this->halRepo = $em->getRepository(HalApplication::CLASS);

        $this->errors = [];
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        $form = [
            'core_id' => $this->application->coreId(),
            'name' => $this->application->name(),
            'hal_app' => $this->application->halApplication() ? $this->application->halApplication()->id() : null
        ];

        if ($this->request->isPost()) {

            $form['core_id'] = $this->request->post('core_id');
            $form['name'] = $this->request->post('name');
            $form['hal_app'] = $this->request->post('hal_app');

            if ($application = $this->handleForm()) {
                // flash and redirect
                $this->flasher
                    ->withFlash(sprintf(self::SUCCESS, $application->name()), 'success')
                    ->load('kraken.application', ['application' => $application->id()]);
            }
        }

        $available = $this->getAvailableRepositories();
        if ($this->application->halApplication()) {
            $id = $this->application->halApplication()->id();
            $name = $this->application->halApplication()->name();
            $available = [$id => $name] + $available;
        }

        $context = [
            'application' => $this->application,
            'errors' => $this->validator->errors(),
            'form' => $form,
            'available' => $available
        ];

        $this->template->render($context);
    }

    /**
     * @return Application|null
     */
    private function handleForm()
    {
        $coreId = $this->request->post('core_id');
        $name = $this->request->post('name');
        $halApp = $this->request->post('hal_app');

        if ($application = $this->validator->isEditValid($this->application, $coreId, $halApp, $name)) {

            // persist to database
            $this->em->merge($application);
            $this->em->flush();
        }

        return $application;
    }

    /**
     * Gets a list of available repositories from Hal that can be linked to Kraken Applications
     *
     * @todo cache this
     *
     * @return array
     */
    private function getAvailableRepositories()
    {
        $applications = $this->applicationRepo->findAll();
        $repos = $this->halRepo->findBy([], ['name' => 'ASC']);

        $available = [];
        foreach ($repos as $repo) {
            $available[$repo->id()] = $repo->name();
        }

        foreach ($applications as $app) {
            if ($app->halApplication()) {
                unset($available[$app->halApplication()->id()]);
            }
        }

        return $available;
    }
}
