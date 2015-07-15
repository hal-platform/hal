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

class PoolsController implements ControllerInterface
{
    use SortingTrait;

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type EntityRepository
     */
    private $viewsRepo;
    private $deploymentRepo;

    /**
     * @type Application
     */
    private $application;

    /**
     * @type Environment
     */
    private $environment;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param Application $application
     * @param Environment $environment
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        Application $application,
        Environment $environment
    ) {
        $this->template = $template;
        $this->viewRepo = $em->getRepository(DeploymentView::CLASS);
        $this->deploymentRepo = $em->getRepository(Deployment::CLASS);

        $this->em = $em;

        $this->application = $application;
        $this->environment = $environment;
    }

    /**
     * {@inheritdoc}
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

        $this->template->render([
            'application' => $this->application,
            'environment' => $this->environment,

            'views' => $views,
            'deployment_pools' => $pools
        ]);

        // $this->bullshit();
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
}
