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
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class ServerController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type EntityRepository
     */
    private $deployRepo;

    /**
     * @type Server
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
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $deployments = $this->deployRepo->findBy(['server' => $this->server]);

        $this->template->render([
            'server' => $this->server,
            'deployments' => $deployments
        ]);
    }
}
