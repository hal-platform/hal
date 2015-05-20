<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller\Configuration\Latest;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Kraken\Entity\ConfigurationProperty;
use QL\Kraken\Entity\Property;
use QL\Kraken\Entity\Target;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class RemovePropertyController implements ControllerInterface
{
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
    private $configurationPropertyRepo;
    private $targetRepo;

    /**
     * @param TemplateInterface $template
     * @param Property $property
     *
     * @param EntityManagerInterface $em
     */
    public function __construct(
        TemplateInterface $template,
        Property $property,
        EntityManagerInterface $em
    ) {
        $this->template = $template;
        $this->property = $property;

        $this->configurationPropertyRepo = $em->getRepository(ConfigurationProperty::CLASS);
        $this->targetRepo = $em->getRepository(Target::CLASS);
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        $context = [
            'application' => $this->property->application(),
            'environment' => $this->property->environment(),
            'property' => $this->property,
            'deployed' => $this->getDeployedProperty()
        ];

        $this->template->render($context);
    }

    /**
     * Get the current config property if it is actively deployed, so we can display a warning.
     *
     * @return ConfigurationProperty|null
     */
    private function getDeployedProperty()
    {
        $target = $this->targetRepo->findOneBy([
            'application' => $this->property->application(),
            'environment' => $this->property->environment()
        ]);

        // No target or configuration was never deployed
        if (!$target || !$target->configuration()) {
            return null;
        }

        $currentProperty = $this->configurationPropertyRepo->findBy([
            'configuration' => $target->configuration(),
            'property' => $this->property
        ]);

        return $currentProperty;
    }
}
