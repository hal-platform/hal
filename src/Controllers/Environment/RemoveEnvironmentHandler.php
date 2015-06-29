<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Environment;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\Server;
use QL\Hal\Core\Repository\EnvironmentRepository;
use QL\Hal\Flasher;
use QL\Panthor\Slim\NotFound;
use QL\Panthor\ControllerInterface;

class RemoveEnvironmentHandler implements ControllerInterface
{
    const SUCCESS = 'Environment "%s" removed.';
    const ERR_HAS_SERVERS = 'Cannot remove environment. All associated servers must first be removed.';

    /**
     * @type EntityRepository
     */
    private $envRepo;
    private $serverRepo;

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
        $this->envRepo = $em->getRepository(Environment::CLASS);
        $this->serverRepo = $em->getRepository(Server::CLASS);
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
        if (!$environment = $this->envRepo->find($this->parameters['id'])) {
            return call_user_func($this->notFound);
        }

        if ($servers = $this->serverRepo->findBy(['environment' => $environment])) {
            return $this->flasher
                ->withFlash(self::ERR_HAS_SERVERS, 'error')
                ->load('environment', ['id' => $this->parameters['id']]);
        }

        $this->em->remove($environment);
        $this->em->flush();

        $message = sprintf(self::SUCCESS, $environment->name());
        return $this->flasher
            ->withFlash($message, 'success')
            ->load('environments');
    }
}
