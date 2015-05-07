<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller\Configuration\Latest;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Kraken\Entity\Application;
use QL\Kraken\Entity\ConfigurationProperty;
use QL\Kraken\Entity\Property;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Slim\NotFound;

class PropertyController implements ControllerInterface
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
     * @type Property
     */
    private $property;

    /**
     * @type EntityRepository
     */
    private $propertyRepo;

    /**
     * @type NotFound
     */
    private $notFound;

    /**
     * @param TemplateInterface $template
     * @param Application $application
     * @param Property $property
     * @param EntityManagerInterface $em
     * @param NotFound $notFound
     */
    public function __construct(
        TemplateInterface $template,
        Application $application,
        Property $property,
        EntityManagerInterface $em,
        NotFound $notFound
    ) {
        $this->template = $template;
        $this->application = $application;
        $this->property = $property;

        $this->propertyRepo = $em->getRepository(ConfigurationProperty::CLASS);

        $this->notFound = $notFound;
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        if ($this->property->application() !== $this->application) {
            return call_user_func($this->notFound);
        }

        $history10 = $this->propertyRepo->findBy(['property' => $this->property], ['created' => 'DESC'], 10);

        $context = [
            'application' => $this->application,
            'environment' => $this->property->environment(),
            'property' => $this->property,
            'history' => $history10
        ];

        $this->template->render($context);
    }
}
