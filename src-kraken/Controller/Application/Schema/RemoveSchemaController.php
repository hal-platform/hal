<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller\Application\Schema;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Kraken\Entity\Application;
use QL\Kraken\Entity\Property;
use QL\Kraken\Entity\Schema;
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
     * @type Application
     */
    private $application;

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
        Application $application,
        Schema $schema,
        EntityManagerInterface $em
    ) {
        $this->template = $template;
        $this->application = $application;
        $this->schema = $schema;

        $this->propertyRepo = $em->getRepository(Property::CLASS);
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        // @todo permissions would be handled per app/env
        // if ($this->schema->application() !== $this->application) {
        //     return call_user_func($this->notFound);
        // }

        $properties = $this->propertyRepo->findBy(['schema' => $this->schema]);
        usort($properties, $this->sorterPropertyByEnvironment());

        $context = [
            'application' => $this->application,
            'schema' => $this->schema,
            'properties' => $properties
        ];

        $this->template->render($context);
    }
}