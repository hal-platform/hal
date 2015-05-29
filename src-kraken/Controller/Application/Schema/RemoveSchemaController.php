<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller\Application\Schema;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Kraken\Core\Entity\Property;
use QL\Kraken\Core\Entity\Schema;
use QL\Kraken\Utility\SortingHelperTrait;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class RemoveSchemaController implements ControllerInterface
{
    use SortingHelperTrait;

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type Schema
     */
    private $schema;

    /**
     * @type EntityRepository
     */
    private $propertyRepo;

    /**
     * @param TemplateInterface $template
     * @param Application $application
     * @param Schema $schema
     *
     * @param EntityManagerInterface $em
     */
    public function __construct(
        TemplateInterface $template,
        Schema $schema,
        EntityManagerInterface $em
    ) {
        $this->template = $template;
        $this->schema = $schema;

        $this->propertyRepo = $em->getRepository(Property::CLASS);
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        $properties = $this->propertyRepo->findBy(['schema' => $this->schema]);
        usort($properties, $this->sorterPropertyByEnvironment());

        $context = [
            'application' => $this->schema->application(),
            'schema' => $this->schema,
            'properties' => $properties
        ];

        $this->template->render($context);
    }
}
