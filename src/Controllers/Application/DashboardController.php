<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Application;

use Doctrine\ORM\EntityManagerInterface;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Service\PoolService;
use Hal\UI\Service\StickyEnvironmentService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
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

class DashboardController implements ControllerInterface
{
    use SortingTrait;
    use TemplatedControllerTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

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
     * @param TemplateInterface $template
     * @param EntityMangerInterface $em
     * @param StickyEnvironmentService $stickyEnvironmentService
     * @param PoolService $poolService
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        StickyEnvironmentService $stickyEnvironmentService,
        PoolService $poolService
    ) {
        $this->template = $template;

        $this->deploymentRepo = $em->getRepository(Deployment::class);
        $this->envRepo = $em->getRepository(Environment::class);

        $this->buildRepo = $em->getRepository(Build::class);
        $this->pushRepo = $em->getRepository(Push::class);

        $this->stickyEnvironmentService = $stickyEnvironmentService;
        $this->poolService = $poolService;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $application = $request->getAttribute(Application::class);

        // environments, selected env
        $environments = $this->getBuildableEnvironments($application);
        $selectedEnvironment = $this->findSelectedEnvironment(
            $environments,
            $this->stickyEnvironmentService->get($request, $application->id())
        );

        $deployments = $builds = $views = [];
        $selectedView = null;

        if ($selectedEnvironment) {
            $deployments = $this->getDeploymentsForEnvironment($application, $selectedEnvironment);
            $builds = $this->buildRepo->findBy(
                ['application' => $application, 'environment' => $selectedEnvironment],
                ['created' => 'DESC'],
                10
            );

            // views, selected view
            $views = $this->poolService->getViews($application, $selectedEnvironment);
            $selectedView = $this->poolService->findSelectedView($request, $application, $selectedEnvironment, $views);
        }

        return $this->withTemplate($request, $response, $this->template, [
            'application' => $application,
            'builds' => $builds,
            'environments' => $environments,
            'deployment_statuses' => $deployments,
            'selected_environment' => $selectedEnvironment,

            'views' => $views,
            'selected_view' => $selectedView,
        ]);
    }

    /**
     * @param Application $application
     *
     * @return Environment[]
     */
    private function getBuildableEnvironments(Application $application)
    {
        $environments = $this->envRepo->getBuildableEnvironmentsByApplication($application);

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
     * @param Application $application
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
    private function getDeploymentsForEnvironment(Application $application, Environment $selectedEnvironment = null)
    {
        $deployments = [];
        if ($selectedEnvironment) {
            $deployments = $this->deploymentRepo->getDeploymentsByApplicationEnvironment($application, $selectedEnvironment);
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
