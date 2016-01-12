<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
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
     * @var EntityRepository
     */
    private $applicationRepo;
    private $deploymentRepo;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var Flasher
     */
    private $flasher;

    /**
     * @var Application
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
