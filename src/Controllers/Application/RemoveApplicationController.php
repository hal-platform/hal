<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Application;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Flasher;
use QL\Panthor\ControllerInterface;

class RemoveApplicationController implements ControllerInterface
{
    const SUCCESS = 'Application "%s" removed.';
    const ERR_HAS_DEPLOYMENTS = 'Cannot remove application. All server deployments must first be removed.';

    /**
     * @type EntityRepository
     */
    private $applicationRepo;
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
     * @type Application
     */
    private $application;

    /**
     * @param EntityManagerInterface $em
     * @param Flasher $flasher
     * @param Application $application
     */
    public function __construct(
        EntityManagerInterface $em,
        Flasher $flasher,
        Application $application
    ) {
        $this->applicationRepo = $em->getRepository(Application::CLASS);
        $this->deploymentRepo = $em->getRepository(Deployment::CLASS);
        $this->em = $em;

        $this->flasher = $flasher;
        $this->application = $application;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        if ($deployments = $this->deploymentRepo->findBy(['application' => $this->application])) {
            return $this->flasher
                ->withFlash(self::ERR_HAS_DEPLOYMENTS, 'error')
                ->load('application', ['application' => $repo->id()]);
        }

        $this->em->remove($this->application);
        $this->em->flush();

        $message = sprintf(self::SUCCESS, $this->application->key());
        return $this->flasher
            ->withFlash($message, 'success')
            ->load('applications');
    }
}
