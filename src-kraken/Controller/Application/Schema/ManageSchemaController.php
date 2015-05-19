<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller\Application\Schema;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use QL\Kraken\Entity\Application;
use QL\Kraken\Entity\Property;
use QL\Kraken\Entity\Schema;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class ManageSchemaController implements ControllerInterface
{
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
    private $propertyRepo;
    private $schemaRepo;

    /**
     * @param TemplateInterface $template
     * @param Application $application
     * @param EntityManager $em
     */
    public function __construct(
        TemplateInterface $template,
        Application $application,
        EntityManager $em
    ) {
        $this->template = $template;
        $this->application = $application;

        $this->propertyRepo = $em->getRepository(Property::CLASS);
        $this->schemaRepo = $em->getRepository(Schema::CLASS);
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        $schemas = $this->schemaRepo->findBy([
            'application' => $this->application
        ], ['key' => 'ASC']);

        $context = [
            'application' => $this->application,
            'configuration_schema' => $schemas
        ];

        $this->template->render($context);
    }
}
