<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Server;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Server;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class ServerController implements ControllerInterface
{
    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityRepository
     */
    private $deployRepo;

    /**
     * @var Server
     */
    private $server;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param Server $server
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        Server $server
    ) {
        $this->template = $template;

        $this->deployRepo = $em->getRepository(Deployment::CLASS);

        $this->server = $server;
    }

    /**
     * @inheritDoc
     */
    public function __invoke()
    {
        $deployments = $this->deployRepo->findBy(['server' => $this->server]);

        usort($deployments, function ($a, $b) {
            $appA = $a->application()->name();
            $appB = $b->application()->name();

            return strcasecmp($appA, $appB);
        });

        $this->template->render([
            'server' => $this->server,
            'deployments' => $deployments
        ]);
    }
}
