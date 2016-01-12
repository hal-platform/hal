<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Kraken\Controller\Configuration\Latest;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Kraken\Core\Entity\Property;
use QL\Kraken\Core\Entity\Snapshot;
use QL\Kraken\Core\Utility\SortingTrait;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class PropertyController implements ControllerInterface
{
    use SortingTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var Property
     */
    private $property;

    /**
     * @var EntityRepository
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
