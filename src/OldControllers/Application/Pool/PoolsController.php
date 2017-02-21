<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Application\Pool;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Service\StickyPoolService;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\DeploymentPool;
use QL\Hal\Core\Entity\DeploymentView;
use QL\Hal\Core\Utility\SortingTrait;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class PoolsController implements ControllerInterface
{
    use SortingTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var StickyPoolService
     */
    private $stickyPoolService;

    /**
     * @var EntityRepository
     */
    private $viewsRepo;
    private $deploymentRepo;

    /**
     * @var Application
     */
    private $application;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @param TemplateInterface $template
     * @param StickyPoolService $stickyPoolService
     * @param EntityManagerInterface $em
     * @param Application $application
     * @param Environment $environment
     */
    public function __construct(
        TemplateInterface $template,
        StickyPoolService $stickyPoolService,
        EntityManagerInterface $em,
        Application $application,
        Environment $environment
    ) {
        $this->template = $template;
        $this->stickyPoolService = $stickyPoolService;

        $this->viewRepo = $em->getRepository(DeploymentView::CLASS);
        $this->deploymentRepo = $em->getRepository(Deployment::CLASS);

        $this->application = $application;
        $this->environment = $environment;
    }

    /**
     * @inheritDoc
     */
    public function __invoke()
    {
        $views = $this->viewRepo->findBy([
            'application' => $this->application,
            'environment' => $this->environment
        ]);

        // Views sorted by name
        usort($views, function(DeploymentView $a, DeploymentView $b) {
            return strcasecmp($a->name(), $b->name());
        });

        $pools = [];
        foreach ($views as $view) {
            $pools += $this->sortPools($view->pools()->toArray());
        }

        $selectedView = $this->stickyPoolService->get($this->application->id(), $this->environment->id());

        $this->template->render([
            'application' => $this->application,
            'environment' => $this->environment,

            'views' => $views,
            'deployment_pools' => $pools,
            'server_collisions' => $this->findServerCollisions($pools),

            'selected_view' => $selectedView
        ]);
    }

    /**
     * @param DeploymentPool[] $pools
     *
     * @return array
     * [
     *    pool.id:
     *        - deployment
     *        - deployment
     *    pool.id:
     *        - deployment
     *        - deployment
     *  ]
     */
    private function sortPools(array $pools)
    {
        $indexed = [];
        foreach ($pools as $pool) {
            $deployments = $pool->deployments()->toArray();
            usort($deployments, $this->deploymentSorter());

            $indexed[$pool->id()] = $deployments;
        }

        return $indexed;
    }

    /**
     * Find if a server has been applied to multiple deployments. Only apps that do this should be punished with excess noise.
     *
     * @param array $pools
     *
     * @return array
     */
    private function findServerCollisions(array $pools)
    {
        $servers = [];
        $collisions = [];

        foreach ($pools as $pool) {
            foreach ($pool as $deployment) {
                $id = $deployment->server()->id();
                if (isset($servers[$id]) && $servers[$id] !== $deployment->id()) {
                    $collisions[$id] = true;
                } else {
                    $servers[$id] = $deployment->id();
                }
            }
        }

        return $collisions;
    }
}
