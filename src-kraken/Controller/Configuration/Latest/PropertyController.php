<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller\Configuration\Latest;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Kraken\Core\Entity\Property;
use QL\Kraken\Core\Entity\Snapshot;
use QL\Kraken\Utility\SortingTrait;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class PropertyController implements ControllerInterface
{
    use SortingTrait;

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type Property
     */
    private $property;

    /**
     * @type EntityRepository
     */
    private $snapshotRepo;
    private $propertyRepo;

    /**
     * @param TemplateInterface $template
     * @param Property $property
     * @param EntityManagerInterface $em
     * @param NotFound $notFound
     */
    public function __construct(
        TemplateInterface $template,
        Property $property,
        EntityManagerInterface $em
    ) {
        $this->template = $template;
        $this->property = $property;

        $this->snapshotRepo = $em->getRepository(Snapshot::CLASS);
        $this->propertyRepo = $em->getRepository(Property::CLASS);
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        $history10 = $this->snapshotRepo->findBy(['property' => $this->property], ['created' => 'DESC'], 10);

        $context = [
            'application' => $this->property->application(),
            'environment' => $this->property->environment(),
            'property' => $this->property,
            'history' => $history10,
            'otherEnvironments' => $this->getValueFromAllEnvironments()
        ];

        $this->template->render($context);
    }

    /**
     * @return Property[]
     */
    public function getValueFromAllEnvironments()
    {
        $properties = $this->propertyRepo->findBy(['schema' => $this->property->schema()]);
        if (count($properties) === 1) {
            return [];
        }

        usort($properties, $this->sorterPropertyByEnvironment());

        return $properties;
    }
}
