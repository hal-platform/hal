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
use QL\Hal\Flasher;
use QL\Hal\Service\PoolService;
use QL\Panthor\MiddlewareInterface;

class RemoveViewHandler implements MiddlewareInterface
{
    const SUCCESS = 'Deployment View "%s" removed.';

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
     * @param EntityManagerInterface $em
     * @param Flasher $flasher
     * @param DeploymentView $view
     */
    public function __construct(
        EntityManagerInterface $em,
        Flasher $flasher,
        PoolService $poolService,
        DeploymentView $view
    ) {
        $this->em = $em;

        $this->flasher = $flasher;
        $this->poolService = $poolService;

        $this->view = $view;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $this->em->remove($this->view);
        $this->em->flush();

        $this->poolService->clearViewCache($this->view);

        $message = sprintf(self::SUCCESS, $this->view->name());
        $this->flasher
            ->withFlash($message, 'success')
            ->load('pools', [
                'application' => $this->view->application()->id(),
                'environment' => $this->view->environment()->id()
            ]);
    }
}
