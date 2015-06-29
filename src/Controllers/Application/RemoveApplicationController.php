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
use QL\Panthor\Slim\NotFound;
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
        $this->applicationRepo = $em->getRepository(Application::CLASS);
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
        if (!$application = $this->applicationRepo->find($this->parameters['id'])) {
            return call_user_func($this->notFound);
        }

        if ($deployments = $this->deploymentRepo->findBy(['application' => $application])) {
            return $this->flasher
                ->withFlash(self::ERR_HAS_DEPLOYMENTS, 'error')
                ->load('repository', ['id' => $repo->id()]);
        }

        $this->em->remove($application);
        $this->em->flush();

        $message = sprintf(self::SUCCESS, $application->key());
        return $this->flasher
            ->withFlash($message, 'success')
            ->load('repositories');
    }
}
