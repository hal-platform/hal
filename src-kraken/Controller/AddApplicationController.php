<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Application as HalApplication;
use QL\Hal\Flasher;
use QL\Kraken\Core\Entity\Application;
use QL\Kraken\Validator\ApplicationValidator;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
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
     * @param ApplicationValidator $validator
     */
    public function __construct(
        Request $request,
        TemplateInterface $template,
        Flasher $flasher,
        EntityManagerInterface $em,
        ApplicationValidator $validator
    ) {
        $this->request = $request;
        $this->template = $template;
        $this->flasher = $flasher;
        $this->validator = $validator;

        $this->em = $em;
        $this->applicationRepo = $this->em->getRepository(Application::CLASS);
        $this->halRepo = $this->em->getRepository(HalApplication::CLASS);
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
            'available' => $this->getAvailableGroupedApplications()
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
    private function getAvailableGroupedApplications()
    {
        $groupedApplications = $this->halRepo->getGroupedApplications();

        $taken = [];
        foreach ($this->applicationRepo->findAll() as $app) {
            if ($app->halApplication()) {
                $taken[$app->halApplication()->id()] = true;
            }
        }
        foreach ($groupedApplications as $index => $grouped) {
            $groupedApplications[$index] = array_filter($grouped, function($app) use ($taken) {
                return !isset($taken[$app->id()]);
            });

        }

        foreach ($groupedApplications as $index => $grouped) {
            if (count($grouped) == 0) {
                unset($groupedApplications[$index]);
            }
        }

        return $groupedApplications;
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
