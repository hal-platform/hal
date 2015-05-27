<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller\Configuration\Latest;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Kraken\Core\Entity\ConfigurationProperty;
use QL\Kraken\Core\Entity\Property;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class PropertyController implements ControllerInterface
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
    private $configPropertyRepo;

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

        $this->configPropertyRepo = $em->getRepository(ConfigurationProperty::CLASS);
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        $history10 = $this->configPropertyRepo->findBy(['property' => $this->property], ['created' => 'DESC'], 10);

        $context = [
            'application' => $this->property->application(),
            'environment' => $this->property->environment(),
            'property' => $this->property,
            'history' => $history10
        ];

        $this->template->render($context);
    }
}
