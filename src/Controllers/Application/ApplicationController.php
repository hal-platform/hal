<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Controllers\Application;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Deployment;
use QL\Kraken\Core\Entity\Application as KrakenApplication;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class ApplicationController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type EntityRepository
     */
    private $deploymentRepo;
    private $krakenRepo;

    /**
     * @type Application
     */
    private $application;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em,
     * @param Application $application
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        Application $application
    ) {
        $this->template = $template;

        $this->deploymentRepo = $em->getRepository(Deployment::CLASS);
        $this->krakenRepo = $em->getRepository(KrakenApplication::CLASS);

        $this->application = $application;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $krakenApp = $this->krakenRepo->findOneBy(['halApplication' => $this->application]);

        $deployments = $this->deploymentRepo->findBy(['application' => $this->application]);

        $this->template->render([
            'application' => $this->application,
            'kraken' => $krakenApp,
            'has_deployments' => (count($deployments) > 0)
        ]);
    }
}
