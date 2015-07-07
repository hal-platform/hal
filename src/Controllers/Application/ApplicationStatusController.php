<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Application;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Repository\BuildRepository;
use QL\Hal\Core\Repository\DeploymentRepository;
use QL\Hal\Core\Repository\EnvironmentRepository;
use QL\Hal\Core\Repository\PushRepository;
use QL\Hal\Core\Utility\SortingTrait;
use QL\Hal\Service\StickyEnvironmentService;
use QL\Kraken\Core\Entity\Application as KrakenApplication;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class ApplicationStatusController implements ControllerInterface
{
    use SortingTrait;

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type EntityRepository
     */
    private $applicationRepo;
    private $krakenRepo;

    /**
     * @type BuildRepository
     */
    private $buildRepo;

    /**
     * @type DeploymentRepository
     */
    private $deploymentRepo;

    /**
     * @type PushRepository
     */
    private $pushRepo;

    /**
     * @type EnvironmentRepository
     */
    private $envRepo;

    /**
     * @type StickyEnvironmentService
     */
    private $stickyService;

    /**
     * @type Application
     */
    private $application;

    /**
     * @param TemplateInterface $template
     * @param EntityMangerInterface $em
     *
     * @param StickyEnvironmentService $stickyService
     * @param Application $application
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,

        StickyEnvironmentService $stickyService,
        Application $application
    ) {
        $this->template = $template;

        $this->applicationRepo = $em->getRepository(Application::CLASS);
        $this->deploymentRepo = $em->getRepository(Deployment::CLASS);
        $this->envRepo = $em->getRepository(Environment::CLASS);

        $this->buildRepo = $em->getRepository(Build::CLASS);
        $this->pushRepo = $em->getRepository(Push::CLASS);

        $this->krakenRepo = $em->getRepository(KrakenApplication::CLASS);

        $this->stickyService = $stickyService;
        $this->application = $application;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $selected = $this->stickyService->get($this->application->id());

        $environments = $this->envRepo->getBuildableEnvironmentsByApplication($this->application);
        // if empty, throw them a bone with "test"
        if (!$environments) {
            $environments = $this->envRepo->findBy(['name' => 'test']);
        }

        $selectedEnvironment = $this->findSelectedEnvironment($environments, $selected);

        $deployments = [];
        if ($selectedEnvironment) {
            $deployments = $this->deploymentRepo->getDeploymentsByApplicationEnvironment($this->application, $selectedEnvironment);
        }

        usort($deployments, $this->deploymentSorter());

        // THIS QUERY SUCKS! BIG TIME.
        // $pushes = $this->pushRepo->getMostRecentByDeployments($deployments);
        foreach ($deployments as &$deployment) {
            $deployment = [
                'deploy' => $deployment,
                'latest' => $this->pushRepo->getMostRecentByDeployment($deployment)
            ];
        }

        $builds = $this->buildRepo->findBy(['application' => $this->application, 'environment' => $selectedEnvironment], ['created' => 'DESC'], 10);

        $krakenApp = $this->krakenRepo->findOneBy(['halApplication' => $this->application]);

        $this->template->render([
            'application' => $this->application,
            'builds' => $builds,
            'environments' => $environments,
            'deployment_statuses' => $deployments,
            'selected_environment' => $selectedEnvironment,

            'kraken' => $krakenApp
        ]);
    }

    /**
     * @param Environment[] $environments
     * @param string $selected
     *
     * @return Environment|null
     */
    private function findSelectedEnvironment($environments, $selected)
    {
        // list empty
        if (!$environments) {
            return null;
        }

        // Find the selected environment
        foreach ($environments as $environment) {
            if ($selected == $environment->id()) {
                return $environment;
            }
        }

        // Not in the list? Just get the first
        return $environments[0];
    }
}
