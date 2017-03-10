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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\DeploymentPool;
use QL\Hal\Core\Entity\DeploymentView;
use QL\Hal\Core\Utility\SortingTrait;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class ViewController implements ControllerInterface
{
    use SortingTrait;
    use TemplatedControllerTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityRepository
     */
    private $deploymentRepo;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     */
    public function __construct(TemplateInterface $template, EntityManagerInterface $em)
    {
        $this->template = $template;
        $this->deploymentRepo = $em->getRepository(Deployment::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $application = $request->getAttribute(Application::class);
        $view = $request->getAttribute(DeploymentView::class);

        // Pools sorted by name
        $pools = $view->pools()->toArray();
        usort($pools, function(DeploymentPool $a, DeploymentPool $b) {
            return strcasecmp($a->name(), $b->name());
        });

        $deployments = $this->deploymentRepo->getDeploymentsByApplicationEnvironment($view->application(), $view->environment());
        usort($deployments, $this->deploymentSorter());

        $sortedPools = $this->sortPools($pools);

        return $this->withTemplate($request, $response, $this->template, [
            'application' => $application,
            'environment' => $view->environment(),

            'view' => $view,
            'pools' => $pools,
            'deployment_pools' => $sortedPools,
            'deployments' => $deployments,

            'server_collisions' => $this->findServerCollisions($deployments)
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
     * @param Deployment[] $deployments
     *
     * @return array
     */
    private function findServerCollisions(array $deployments)
    {
        $servers = [];
        $collisions = [];

        foreach ($deployments as $deployment) {
            $id = $deployment->server()->id();

            if (isset($servers[$id])) {
                $collisions[$id] = true;
            } else {
                $servers[$id] = true;
            }
        }

        return $collisions;
    }
}
