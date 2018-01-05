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
use Hal\UI\SharedStaticConfiguration;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Hal\Core\Entity\Application;
use Hal\Core\Entity\Target;
use Hal\Core\Entity\Environment;
use Hal\Core\Entity\JobType\Build;
use Hal\Core\Repository\BuildRepository;
use Hal\Core\Repository\TargetRepository;
use Hal\Core\Repository\EnvironmentRepository;
use Hal\Core\Utility\SortingTrait;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class ApplicationDashboardController implements ControllerInterface
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

        $this->stickyEnvironmentService = $stickyEnvironmentService;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $application = $request->getAttribute(Application::class);

        $environments = $this->environmentRepository->getBuildableEnvironmentsByApplication($application);
        $stickyEnv = $this->stickyEnvironmentService->get($request, $application->id());
        $selectedEnvironment = $this->findSelectedEnvironment($environments, $stickyEnv);

        $targets = $this->getTargetsForEnvironment($application, $selectedEnvironment);
        $builds = $this->getBuilds($application, $selectedEnvironment);

        return $this->withTemplate($request, $response, $this->template, [
            'application' => $application,
            'builds' => $builds,
            'environments' => $environments,
            'targets' => $targets,
            'selected_environment' => $selectedEnvironment,
        ]);
    }

    /**
     * @param Environment[] $environments
     * @param string $selected
     *
     * @return Environment|null
     */
    private function findSelectedEnvironment(array $environments, $selected)
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

        return null;
    }

    /**
     * @param Application $application
     * @param Environment|null $selectedEnvironment
     *
     * @return array
     */
    private function getTargetsForEnvironment(Application $application, ?Environment $selectedEnvironment)
    {
        $targets = [];

        if ($selectedEnvironment) {
            $targets = $this->targetRepository->getByApplicationAndEnvironment($application, $selectedEnvironment);
        }

        usort($targets, $this->targetSorter());

        return $targets;
    }

    /**
     * @param Application $application
     * @param Environment|null $selectedEnvironment
     *
     * @return array
     */
    private function getBuilds(Application $application, ?Environment $selectedEnvironment)
    {
        $searchBy = $selectedEnvironment ? [$selectedEnvironment, null] : [null];
        $sortBy = [
            'created' => 'DESC'
        ];

        $builds = $this->buildRepository->findBy(
            [
                'application' => $application,
                'environment' => $searchBy
            ],
            $sortBy,
            SharedStaticConfiguration::SMALL_PAGE_SIZE
        );

        return $builds;
    }
}
