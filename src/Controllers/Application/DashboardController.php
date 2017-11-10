<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Application;

use Doctrine\ORM\EntityManagerInterface;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Service\StickyEnvironmentService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Hal\Core\Entity\Application;
use Hal\Core\Entity\Build;
use Hal\Core\Entity\Target;
use Hal\Core\Entity\Environment;
use Hal\Core\Entity\Release;
use Hal\Core\Repository\BuildRepository;
use Hal\Core\Repository\TargetRepository;
use Hal\Core\Repository\EnvironmentRepository;
use Hal\Core\Repository\ReleaseRepository;
use Hal\Core\Utility\SortingTrait;
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
    private $buildRepository;

    /**
     * @var TargetRepository
     */
    private $targetRepository;

    /**
     * @var ReleaseRepository
     */
    private $releaseRepository;

    /**
     * @var EnvironmentRepository
     */
    private $environmentRepository;

    /**
     * @var StickyEnvironmentService
     */
    private $stickyEnvironmentService;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param StickyEnvironmentService $stickyEnvironmentService
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        StickyEnvironmentService $stickyEnvironmentService
    ) {
        $this->template = $template;

        $this->targetRepository = $em->getRepository(Target::class);
        $this->environmentRepository = $em->getRepository(Environment::class);

        $this->buildRepository = $em->getRepository(Build::class);
        $this->releaseRepository = $em->getRepository(Release::class);

        $this->stickyEnvironmentService = $stickyEnvironmentService;
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

        $deployments = $builds =  [];

        if ($selectedEnvironment) {
            $deployments = $this->getTargetsForEnvironment($application, $selectedEnvironment);
            $builds = $this->buildRepository->findBy(
                ['application' => $application, 'environment' => $selectedEnvironment],
                ['created' => 'DESC'],
                10
            );
        }

        return $this->withTemplate($request, $response, $this->template, [
            'application' => $application,
            'builds' => $builds,
            'environments' => $environments,
            'deployment_statuses' => $deployments,
            'selected_environment' => $selectedEnvironment,
        ]);
    }

    /**
     * @param Application $application
     *
     * @return Environment[]
     */
    private function getBuildableEnvironments(Application $application)
    {
        $environments = $this->environmentRepository->getBuildableEnvironmentsByApplication($application);

        // if empty, throw them a bone with "test"
        if (!$environments) {
            $environments = $this->environmentRepository->findBy(['name' => 'test']);
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
     *         'deploy' => Target
     *         'latest' => Release|null
     *     ],
     *     [
     *         'deploy' => Target
     *         'latest' => Release|null
     *     ]
     * ]
     */
    private function getTargetsForEnvironment(Application $application, Environment $selectedEnvironment = null)
    {
        $targets = [];
        if ($selectedEnvironment) {
            $targets = $this->targetRepository->getByApplicationAndEnvironment($application, $selectedEnvironment);
        }

        usort($targets, $this->targetSorter());

        foreach ($targets as &$target) {
            $target = [
                'target' => $target,
                'latest' => $this->releaseRepository->getByTarget($target, 1)->getIterator()->current()
            ];
        }

        return $targets;
    }
}
