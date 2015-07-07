<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Application\Pool;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\Pool;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class PoolsController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type EntityRepository
     */
    private $serverRepo;

    /**
     * @type EnvironmentRepository
     */
    private $environmentRepo;

    /**
     * @type Application
     */
    private $application;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param Application $application
     * @param Environment $environment
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        Application $application,
        Environment $environment
    ) {
        $this->template = $template;
        // $this->poolsRepo = $em->getRepository(Pool::CLASS);

        $this->application = $application;
        $this->environment = $environment;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $this->template->render([
            'application' => $this->application,
            'environment' => $this->environment
        ]);
    }
}
