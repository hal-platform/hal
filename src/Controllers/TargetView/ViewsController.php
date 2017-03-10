<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\TargetView;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Service\StickyPoolService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\DeploymentPool;
use QL\Hal\Core\Entity\DeploymentView;
use QL\Hal\Core\Utility\SortingTrait;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class ViewsController implements ControllerInterface
{
    use SortingTrait;
    use TemplatedControllerTrait;

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

    /**
     * @param TemplateInterface $template
     * @param StickyPoolService $stickyPoolService
     * @param EntityManagerInterface $em
     */
    public function __construct(
        TemplateInterface $template,
        StickyPoolService $stickyPoolService,
        EntityManagerInterface $em
    ) {
        $this->template = $template;
        $this->stickyPoolService = $stickyPoolService;

        $this->viewRepo = $em->getRepository(DeploymentView::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $application = $request->getAttribute(Application::class);
        $environment = $request->getAttribute(Environment::class);

        $views = $this->viewRepo->findBy([
            'application' => $application,
            'environment' => $environment
        ]);

        // Views sorted by name
        usort($views, function(DeploymentView $a, DeploymentView $b) {
            return strcasecmp($a->name(), $b->name());
        });

        $pools = [];
        foreach ($views as $view) {
            $pools += $this->sortPools($view->pools()->toArray());
        }

        $selectedView = $this->stickyPoolService->get($request, $application->id(), $environment->id());

        return $this->withTemplate($request, $response, $this->template, [
            'application' => $application,
            'environment' => $environment,

            'views' => $views,
            'target_pools' => $pools,
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
     *        - target
     *        - target
     *    pool.id:
     *        - target
     *        - target
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
     * Find if a server has been applied to multiple deployments.
     * Only apps that do this should be punished with excess noise.
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
