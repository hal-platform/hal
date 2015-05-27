<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Kraken\Entity\Application;
use QL\Kraken\Validator\ApplicationValidator;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Hal\Core\Entity\Repository as HalApplication;
use QL\Hal\Flasher;
use Slim\Http\Request;

class AddApplicationController implements ControllerInterface
{
    const SUCCESS = 'Application "%s" added.';

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
    private $applicationRepo;
    private $halRepo;

    /**
     * @type Flasher
     */
    private $flasher;

    /**
     * @type ApplicationValidator
     */
    private $validator;

    /**
     * @param Request $request
     * @param TemplateInterface $template
     * @param Flasher $flasher
     * @param EntityManagerInterface $em
     * @param EntityRepository $halRepo
     * @param ApplicationValidator $validator
     */
    public function __construct(
        Request $request,
        TemplateInterface $template,
        Flasher $flasher,
        EntityManagerInterface $em,
        EntityRepository $halRepo,
        ApplicationValidator $validator
    ) {
        $this->request = $request;
        $this->template = $template;
        $this->flasher = $flasher;
        $this->validator = $validator;

        $this->em = $em;
        $this->halRepo = $halRepo;
        $this->applicationRepo = $this->em->getRepository(Application::CLASS);
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        $form = [];

        if ($this->request->isPost()) {

            if ($application = $this->handleForm()) {
                // flash and redirect
                $this->flasher
                    ->withFlash(sprintf(self::SUCCESS, $application->name()), 'success')
                    ->load('kraken.applications');
            }

            $form = [
                'hal_app' => $this->request->post('hal_app'),
                'name' => $this->request->post('name'),
                'core_id' => $this->request->post('core_id')
            ];
        }

        $context = [
            'form' => $form,
            'errors' => $this->validator->errors(),
            'available' => $this->getAvailableRepositories()
        ];

        $this->template->render($context);
    }

    /**
     * Gets a list of available repositories from HAL 9000 that can be linked to Kraken Applications
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
            $available[$repo->getId()] = $repo->getName();
        }

        foreach ($applications as $app) {
            if ($app->halApplication()) {
                unset($available[$app->halApplication()->getId()]);
            }
        }

        return $available;
    }

    /**
     * @return Application|null
     */
    private function handleForm()
    {
        $name = $this->request->post('name');
        $coreId = $this->request->post('core_id');
        $halApp = $this->request->post('hal_app');

        $application = $this->validator->isValid($coreId, $halApp, $name);

        if ($application) {
            // persist to database
            $this->em->persist($application);
            $this->em->flush();
        }

        return $application;
    }
}
