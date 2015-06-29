<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Server;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Server;
use QL\Hal\Core\Type\EnumType\ServerEnum;
use QL\Hal\Flasher;
use QL\Panthor\Slim\NotFound;
use QL\Panthor\ControllerInterface;

class RemoveServerController implements ControllerInterface
{
    const SUCCESS = '';
    CONST ERR_HAS_DEPLOYMENTS = 'Cannot remove server. All associated deployments must first be removed.';

    /**
     * @type EntityRepository
     */
    private $serverRepo;
    private $deployRepo;

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
        $this->serverRepo = $em->getRepository(Server::CLASS);
        $this->deployRepo = $em->getRepository(Deployment::CLASS);
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
        if (!$server = $this->serverRepo->find($this->parameters['id'])) {
            return call_user_func($this->notFound);
        }

        if ($deployments = $this->deployRepo->findBy(['server' => $server])) {
            return $this->flasher
                ->withFlash(self::ERR_HAS_DEPLOYMENTS, 'error')
                ->load('server', ['id' => $server->id()]);
        }

        $this->em->remove($server);
        $this->em->flush();

        $name = $server->name();
        if ($server->type() === ServerEnum::TYPE_EB) {
            $name = 'Elastic Beanstalk';
        } elseif ($server->type() === ServerEnum::TYPE_EC2) {
            $name = 'EC2';
        }

        $message = sprintf('Server "%s" removed.', $name);
        return $this->flasher
            ->withFlash($message, 'success')
            ->load('servers');
    }
}
