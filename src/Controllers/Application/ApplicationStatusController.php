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
use QL\Hal\Core\Entity\DeploymentPool;
use QL\Hal\Core\Entity\DeploymentView;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Repository\BuildRepository;
use QL\Hal\Core\Repository\DeploymentRepository;
use QL\Hal\Core\Repository\EnvironmentRepository;
use QL\Hal\Core\Repository\PushRepository;
use QL\Hal\Core\Utility\SortingTrait;
use QL\Hal\Service\StickyEnvironmentService;
use QL\Hal\Service\StickyViewService;
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
    private $stickyEnvironmentService;

    /**
     * @type StickyViewService
     */
    private $stickyViewService;

    /**
     * @type Application
     */
    private $application;

    /**
     * @param TemplateInterface $template
     * @param EntityMangerInterface $em
     *
     * @param StickyEnvironmentService $stickyEnvironmentService
     * @param StickyViewService $stickyViewService
     * @param Application $application
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,

        StickyEnvironmentService $stickyEnvironmentService,
        StickyViewService $stickyViewService,
        Application $application
    ) {
        $this->template = $template;

        $this->applicationRepo = $em->getRepository(Application::CLASS);
        $this->deploymentRepo = $em->getRepository(Deployment::CLASS);
        $this->envRepo = $em->getRepository(Environment::CLASS);
        $this->viewRepo = $em->getRepository(DeploymentView::CLASS);

        $this->buildRepo = $em->getRepository(Build::CLASS);
        $this->pushRepo = $em->getRepository(Push::CLASS);

        $this->krakenRepo = $em->getRepository(KrakenApplication::CLASS);

        $this->stickyEnvironmentService = $stickyEnvironmentService;
        $this->stickyViewService = $stickyViewService;
        $this->application = $application;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        // environments, selected env
        $environments = $this->getEnvironments();
        $selected = $this->stickyEnvironmentService->get($this->application->id());
        $selectedEnvironment = $this->findSelectedEnvironment($environments, $selected);

        $deployments = $this->getDeploymentsForEnvironment($selectedEnvironment);
        $builds = $this->buildRepo->findBy(['application' => $this->application, 'environment' => $selectedEnvironment], ['created' => 'DESC'], 10);

        $krakenApp = $this->krakenRepo->findOneBy(['halApplication' => $this->application]);

        // views, selected view
        $selected = $this->stickyViewService->get($this->application->id(), $selectedEnvironment->id());
        $views = $this->getViews($selectedEnvironment);
        $selectedView = $this->findSelectedView($views, $selected);

        $this->template->render([
            'application' => $this->application,
            'builds' => $builds,
            'environments' => $environments,
            'deployment_statuses' => $deployments,
            'selected_environment' => $selectedEnvironment,

            'kraken' => $krakenApp,
            'views' => $views,
            'selected_view' => $selectedView
        ]);
    }

    /**
     * @return Environment[]
     */
    private function getEnvironments()
    {
        $environments = $this->envRepo->getBuildableEnvironmentsByApplication($this->application);

        // if empty, throw them a bone with "test"
        if (!$environments) {
            $environments = $this->envRepo->findBy(['name' => 'test']);
        }

        return $environments;
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
        return array_unshift($environments);
    }


    /**
     * @param array $views
     * @param string $selected
     *
     * @return string|null
     */
    private function findSelectedView($views, $selected)
    {
        // list empty
        if (!$views) {
            return null;
        }

        // Find the selected view
        foreach ($views as $id => $view) {
            if ($id === $selected) {
                return $id;
            }
        }

        // default to null = no view
        return null;
    }

    /**
     * @param Environment $selectedEnvironment
     *
     * @return array
     * [
     *     [
     *         'deploy' => Deployment
     *         'latest' => Push|null
     *     ],
     *     [
     *         'deploy' => Deployment
     *         'latest' => Push|null
     *     ]
     * ]
     */
    private function getDeploymentsForEnvironment(Environment $selectedEnvironment = null)
    {
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

        return $deployments;
    }

    /**
     * @param Environment $selectedEnvironment
     *
     * @return array
     * [
     *     'id_1' => [
     *         'name' => 'Name'
     *         'pools' => [
     *             [
     *                 'name' => 'Name'
     *                 'deployments' => [
     *                     'id_1',
     *                     'id_2',
     *                     'id_3'
     *                 ]
     *             ],
     *             [
     *                 'name' => 'Name'
     *                 'deployments' => [
     *                     'id_1',
     *                     'id_2',
     *                     'id_3'
     *                 ]
     *             ]
     *         ]
     *     ]
     * ]
     */
    private function getViews(Environment $selectedEnvironment = null)
    {
        if (!$selectedEnvironment) {
            return [];
        }

        $sorter = function($a, $b) {
            return strcasecmp($a->name(), $b->name());
        };

        $deploymentViews = $this->viewRepo->findBy(['application' => $this->application, 'environment' => $selectedEnvironment]);
        usort($deploymentViews, $sorter);

        $views = [];
        foreach ($deploymentViews as $view) {
            $deploymentPools = $view->pools()->toArray();
            usort($deploymentPools, $sorter);

            $pools = [];
            foreach ($deploymentPools as $pool) {
                $deployments = $pool->deployments()->toArray();

                // skip empty pools
                if (!$deployments) continue;

                usort($deployments, $this->deploymentSorter());

                // hashmap it
                $hashed = [];
                foreach ($deployments as $deployment) {
                    $hashed[$deployment->id()] = true;
                }

                $pools[] = [
                    'name' => $pool->name(),
                    'deployments' => $hashed
                ];
            }

            // skip empty views
            if (!$pools) continue;

            $views[$view->id()] = [
                'name' => $view->name(),
                'pools' => $pools
            ];
        }

        return $views;
    }
}
