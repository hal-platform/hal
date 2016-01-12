<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\DeploymentView;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Utility\SortingTrait;
use MCP\Cache\CachingTrait;

/**
 * Handle sorting, collating of deployment views and pools
 *
 * Should be cache cleared when views or pools are added/updated/deleted.
 */
class PoolService
{
    use CachingTrait;
    use SortingTrait;

    const KEY_VIEWS = 'pools:%s.%s';

    /**
     * @var EntityRepository
     */
    private $viewRepo;

    /**
     * @var StickyPoolService
     */
    private $stickyPoolService;

    /**
     * @param EntityManagerInterface $em
     * @param StickyPoolService $stickyPoolService
     */
    public function __construct(
        EntityManagerInterface $em,
        StickyPoolService $stickyPoolService
    ) {
        $this->viewRepo = $em->getRepository(DeploymentView::CLASS);
        $this->stickyPoolService = $stickyPoolService;
    }

    /**
     * @param Application $application
     * @param Environment $environment
     *
     * @return array
     */
    public function getViews(Application $application, Environment $environment)
    {
        $key = sprintf(self::KEY_VIEWS, $application->id(), $environment->id());

        if (null !== ($data = $this->getFromCache($key))) {
            return $data;
        }

        $data = $this->buildViews($application, $environment);

        $this->setToCache($key, $data);
        return $data;
    }

    /**
     * @param Application $application
     * @param Environment $environment
     * @param array $views
     *
     * @return string|null
     */
    public function findSelectedView(Application $application, Environment $environment, array $views)
    {
        // list empty
        if (!$views) {
            return null;
        }

        $selected = $this->stickyPoolService->get($application->id(), $environment->id());

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
     * @param Application $application
     * @param Environment $environment
     *
     * @return void
     */
    public function clearCache(Application $application, Environment $environment)
    {
        $key = sprintf(self::KEY_VIEWS, $application->id(), $environment->id());

        $this->setToCache($key, null);
    }

    /**
     * @param DeploymentView $view
     *
     * @return void
     */
    public function clearViewCache(DeploymentView $view)
    {
        if (!$view->application() || !$view->environment()) {
            return;
        }

        $this->clearCache($view->application(), $view->environment());
    }

    /**
     * @param Environment $environment
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
    private function buildViews(Application $application, Environment $environment)
    {
        $sorter = function($a, $b) {
            return strcasecmp($a->name(), $b->name());
        };

        $deploymentViews = $this->viewRepo->findBy([
            'application' => $application,
            'environment' => $environment
        ]);

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
                array_walk($deployments, function (&$deployment) {
                    $deployment = $deployment->id();
                });

                $pools[] = [
                    'name' => $pool->name(),
                    'deployments' => $deployments
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
