<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller\Application;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use QL\Kraken\Entity\Application;
use QL\Kraken\Entity\Schema;
use QL\Kraken\Entity\Target;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class ApplicationStatusController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type EntityRepository
     */
    private $tarRepository;
    private $schemaRepository;

    /**
     * @param TemplateInterface $template
     * @param Application $application
     * @param EntityManager $em
     */
    public function __construct(
        TemplateInterface $template,
        Application $application,
        $em
    ) {
        $this->template = $template;
        $this->application = $application;

        $this->tarRepository = $em->getRepository(Target::CLASS);
        $this->schemaRepository = $em->getRepository(Schema::CLASS);
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        $targets = $this->tarRepository->findBy(['application' => $this->application]);

        $schema = $this->schemaRepository->findBy([
            'application' => $this->application
        ], ['key' => 'ASC']);

        // Cross reference checksum of current value in Consul with checksum of "active" configuration in DB

        $context = [
            'application' => $this->application,
            'targets' => $targets,
            'schema' => $schema
        ];

        $this->template->render($context);
    }
}
