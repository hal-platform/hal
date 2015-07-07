<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Application\Deployment;

use Doctrine\ORM\EntityManagerInterface;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Flasher;
use QL\Panthor\ControllerInterface;

class RemoveDeploymentHandler implements ControllerInterface
{
    /**
     * @type EntityManagerInterface
     */
    private $em;

    /**
     * @type Flasher
     */
    private $flasher;

    /**
     * @type Deployment
     */
    private $deployment;

    /**
     * @param EntityManagerInterface $em
     * @param Flasher $flasher
     * @param Deployment $deployment
     */
    public function __construct(
        EntityManagerInterface $em,
        Flasher $flasher,
        Deployment $deployment
    ) {
        $this->em = $em;
        $this->flasher = $flasher;

        $this->deployment = $deployment;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $this->em->remove($this->deployment);
        $this->em->flush();

        return $this->flasher
            ->withFlash('Deployment removed.', 'success')
            ->load('deployments', ['application' => $this->deployment->application()->id()]);
    }
}
