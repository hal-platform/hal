<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller\Application;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Kraken\Core\Entity\Application;
use QL\Kraken\Core\Entity\Schema;
use QL\Kraken\Core\Entity\Target;
use QL\Kraken\Utility\SortingTrait;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class ApplicationStatusController implements ControllerInterface
{
    use SortingTrait;

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type Application
     */
    private $application;

    /**
     * @type EntityRepository
     */
    private $targetRepo;
    private $schemaRepo;

    /**
     * @param TemplateInterface $template
     * @param Application $application
     * @param ConsulService $consul
     * @param EntityManagerInterface $em
     */
    public function __construct(
        TemplateInterface $template,
        Application $application,
        EntityManagerInterface $em
    ) {
        $this->template = $template;
        $this->application = $application;

        $this->targetRepo = $em->getRepository(Target::CLASS);
        $this->schemaRepo = $em->getRepository(Schema::CLASS);
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        $targets = $this->targetRepo->findBy(['application' => $this->application]);

        usort($targets, $this->targetSorter());

        $schema = $this->schemaRepo->findBy([
            'application' => $this->application
        ], ['key' => 'ASC']);

        $context = [
            'application' => $this->application,
            'targets' => $targets,
            'schema' => $schema
        ];

        $this->template->render($context);
    }
}
