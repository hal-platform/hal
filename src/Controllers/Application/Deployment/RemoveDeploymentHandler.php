<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Application\Deployment;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Flasher;
use QL\Panthor\Slim\NotFound;
use QL\Panthor\ControllerInterface;

class RemoveDeploymentHandler implements ControllerInterface
{
    /**
     * @type EntityRepository
     */
    private $deploymentRepo;

    /**
     * @type EntityManagerInterface
     */
    private $em;

    /**
     * @type Flasher
     */
    private $flasher;

    /**
     * @type NotFound
     */
    private $notFound;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param EntityManagerInterface $em
     * @param Flasher $flasher
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        EntityManagerInterface $em,
        Flasher $flasher,
        NotFound $notFound,
        array $parameters
    ) {
        $this->deploymentRepo = $em->getRepository(Deployment::CLASS);
        $this->em = $em;
        $this->flasher = $flasher;

        $this->notFound = $notFound;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        if (!$deployment = $this->deploymentRepo->find($this->parameters['id'])) {
            return call_user_func($this->notFound);
        }

        $this->em->remove($deployment);
        $this->em->flush();

        return $this->flasher
            ->withFlash('Deployment removed.', 'success')
            ->load('repository.deployments', ['repository' => $this->parameters['repository']]);
    }
}
