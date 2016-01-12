<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Controllers\Application\Pool;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\DeploymentView;
use QL\Hal\Core\Entity\DeploymentPool;
use QL\Hal\Flasher;
use QL\Hal\Service\PoolService;
use QL\Panthor\MiddlewareInterface;

class RemovePoolHandler implements MiddlewareInterface
{
    const SUCCESS = 'Deployment Pool "%s" removed.';

    /**
     * @type EntityManagerInterface
     */
    private $em;

    /**
     * @type Flasher
     */
    private $flasher;

    /**
     * @type PoolService
     */
    private $poolService;

    /**
     * @type DeploymentView
     */
    private $view;

    /**
     * @type DeploymentPool
     */
    private $pool;

    /**
     * @param EntityManagerInterface $em
     * @param Flasher $flasher
     * @param DeploymentView $view
     * @param DeploymentPool $pool
     */
    public function __construct(
        EntityManagerInterface $em,
        Flasher $flasher,
        PoolService $poolService,
        DeploymentView $view,
        DeploymentPool $pool
    ) {
        $this->em = $em;

        $this->flasher = $flasher;
        $this->poolService = $poolService;

        $this->view = $view;
        $this->pool = $pool;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $this->em->remove($this->pool);
        $this->em->flush();

        $this->poolService->clearViewCache($this->view);

        $message = sprintf(self::SUCCESS, $this->pool->name());
        $this->flasher
            ->withFlash($message, 'success')
            ->load('deployment_view', ['view' => $this->view->id()]);
    }
}
