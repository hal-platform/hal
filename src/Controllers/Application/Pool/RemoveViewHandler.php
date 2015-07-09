<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Application\Pool;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\DeploymentView;
use QL\Hal\Flasher;
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
        DeploymentView $view
    ) {
        $this->em = $em;

        $this->flasher = $flasher;

        $this->view = $view;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $this->em->remove($this->view);
        $this->em->flush();

        $message = sprintf(self::SUCCESS, $this->view->name());
        $this->flasher
            ->withFlash($message, 'success')
            ->load('pools', [
                'application' => $this->view->application()->id(),
                'environment' => $this->view->environment()->id()
            ]);
    }
}
