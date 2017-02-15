<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Server;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Flasher;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Server;
use QL\Hal\Core\Type\EnumType\ServerEnum;
use QL\Panthor\ControllerInterface;

class RemoveServerController implements ControllerInterface
{
    const SUCCESS = '';
    CONST ERR_HAS_DEPLOYMENTS = 'Cannot remove server. All associated deployments must first be removed.';

    /**
     * @var EntityRepository
     */
    private $deployRepo;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var Flasher
     */
    private $flasher;

    /**
     * @var Server
     */
    private $server;

    /**
     * @param EntityManagerInterface $em
     * @param Flasher $flasher
     * @param Server $server
     */
    public function __construct(
        EntityManagerInterface $em,
        Flasher $flasher,
        Server $server
    ) {
        $this->deployRepo = $em->getRepository(Deployment::CLASS);
        $this->em = $em;

        $this->flasher = $flasher;
        $this->server = $server;
    }

    /**
     * @inheritDoc
     */
    public function __invoke()
    {
        if ($deployments = $this->deployRepo->findBy(['server' => $this->server])) {
            return $this->flasher
                ->withFlash(self::ERR_HAS_DEPLOYMENTS, 'error')
                ->load('server', ['server' => $this->server->id()]);
        }

        $this->em->remove($this->server);
        $this->em->flush();

        $name = $this->server->name();
        if ($this->server->type() === ServerEnum::TYPE_EB) {
            $name = 'Elastic Beanstalk';
        } elseif ($this->server->type() === ServerEnum::TYPE_CD) {
            $name = 'Code Deploy';
        } elseif ($this->server->type() === ServerEnum::TYPE_S3) {
            $name = 'S3';
        }

        $message = sprintf('Server "%s" removed.', $name);
        return $this->flasher
            ->withFlash($message, 'success')
            ->load('servers');
    }
}
