<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Application\Pool;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\DeploymentPool;
use QL\Hal\Core\Entity\DeploymentView;
use QL\Hal\Core\Utility\SortingTrait;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class ViewController implements ControllerInterface
{
    use SortingTrait;

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type EntityRepository
     */
    private $deploymentRepo;

    /**
     * @type DeploymentView
     */
    private $view;

    /**
     * @type Environment
     */
    private $environment;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param DeploymentView $view
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        DeploymentView $view
    ) {
        $this->template = $template;
        $this->deploymentRepo = $em->getRepository(Deployment::CLASS);

        $this->em = $em;
        $this->view = $view;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        // Pools sorted by name
        $pools = $this->view->pools()->toArray();
        usort($pools, function(DeploymentPool $a, DeploymentPool $b) {
            return strcasecmp($a->name(), $b->name());
        });

        $deployments = $this->deploymentRepo->getDeploymentsByApplicationEnvironment($this->view->application(), $this->view->environment());
        usort($deployments, $this->deploymentSorter());

        $sortedPools = $this->sortPools($pools);

        $this->template->render([
            'application' => $this->view->application(),
            'environment' => $this->view->environment(),

            'view' => $this->view,
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
