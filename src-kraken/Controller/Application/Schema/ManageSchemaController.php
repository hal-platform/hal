<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Kraken\Controller\Application\Schema;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use QL\Kraken\Core\Entity\Application;
use QL\Kraken\Core\Entity\Property;
use QL\Kraken\Core\Entity\Schema;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class ManageSchemaController implements ControllerInterface
{
    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var Application
     */
    private $application;

    /**
     * @var EntityRepository
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
