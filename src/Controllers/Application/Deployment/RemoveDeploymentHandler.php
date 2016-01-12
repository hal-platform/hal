<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Controllers\Application\Deployment;

use Doctrine\ORM\EntityManagerInterface;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Flasher;
use QL\Panthor\ControllerInterface;

class RemoveDeploymentHandler implements ControllerInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var Flasher
     */
    private $flasher;

    /**
     * @var Deployment
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
