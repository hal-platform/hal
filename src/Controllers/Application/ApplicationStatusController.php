<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Application;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Service\PoolService;
use Hal\UI\Service\StickyEnvironmentService;
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
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class ApplicationStatusController implements ControllerInterface
{
    use SortingTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityRepository
     */
    private $applicationRepo;

    /**
     * @var BuildRepository
     */
    private $buildRepo;

    /**
     * @var DeploymentRepository
     */
    private $deploymentRepo;

    /**
     * @var PushRepository
     */
    private $pushRepo;

    /**
     * @var EnvironmentRepository
     */
    private $envRepo;

    /**
     * @var StickyEnvironmentService
     */
    private $stickyEnvironmentService;

    /**
     * @var PoolService
     */
    private $poolService;

    /**
     * @var Application
     */
    private $application;

    /**
     * @param TemplateInterface $template
     * @param EntityMangerInterface $em
     *
     * @param StickyEnvironmentService $stickyEnvironmentService
     * @param PoolService $poolService
     * @param Application $application
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,

        StickyEnvironmentService $stickyEnvironmentService,
        PoolService $poolService,
        Application $application
    ) {
        $this->template = $template;

        $this->applicationRepo = $em->getRepository(Application::CLASS);
        $this->deploymentRepo = $em->getRepository(Deployment::CLASS);
        $this->envRepo = $em->getRepository(Environment::CLASS);

        $this->buildRepo = $em->getRepository(Build::CLASS);
        $this->pushRepo = $em->getRepository(Push::CLASS);

        $this->stickyEnvironmentService = $stickyEnvironmentService;
        $this->poolService = $poolService;
        $this->application = $application;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        // environments, selected env
        $environments = $this->getBuildableEnvironments();
        $selected = $this->stickyEnvironmentService->get($this->application->id());
        $selectedEnvironment = $this->findSelectedEnvironment($environments, $selected);

        $deployments = $builds = $views = [];
        $selectedView = null;

        if ($selectedEnvironment) {
            $deployments = $this->getDeploymentsForEnvironment($selectedEnvironment);
            $builds = $this->buildRepo->findBy(
                ['application' => $this->application, 'environment' => $selectedEnvironment],
                ['created' => 'DESC'],
                10
            );

            // views, selected view
            $views = $this->poolService->getViews($this->application, $selectedEnvironment);
            $selectedView = $this->poolService->findSelectedView($this->application, $selectedEnvironment, $views);
        }

        $this->template->render([
            'application' => $this->application,
            'builds' => $builds,
            'environments' => $environments,
            'deployment_statuses' => $deployments,
            'selected_environment' => $selectedEnvironment,

            'views' => $views,
            'selected_view' => $selectedView,
        ]);
    }

    /**
     * @return Environment[]
     */
    private function getBuildableEnvironments()
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
        return array_shift($environments);
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

        foreach ($deployments as &$deployment) {
            $deployment = [
                'deploy' => $deployment,
                'latest' => $this->pushRepo->getMostRecentByDeployment($deployment)
            ];
        }

        return $deployments;
    }
}
