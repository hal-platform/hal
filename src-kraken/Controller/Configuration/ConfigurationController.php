<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller\Configuration;

use Doctrine\ORM\EntityRepository;
use QL\Kraken\Entity\Application;
use QL\Kraken\Entity\Configuration;
use QL\Kraken\Entity\ConfigurationProperty;
use QL\Kraken\Entity\Target;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class ConfigurationController implements ControllerInterface
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
     * @type Configuration
     */
    private $configuration;

    /**
     * @type EntityRepository
     */
    private $targetRepository;
    private $propRepository;

    /**
     * @param TemplateInterface $template
     * @param Application $application
     * @param Configuration $configuration
     *
     * @param $em
     */
    public function __construct(
        TemplateInterface $template,
        Application $application,
        Configuration $configuration,
        $em
    ) {
        $this->template = $template;
        $this->application = $application;
        $this->configuration = $configuration;

        $this->targetRepository = $em->getRepository(Target::CLASS);
        $this->propRepository = $em->getRepository(ConfigurationProperty::CLASS);
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        $target = $this->targetRepository->findOneBy(['configuration' => $this->configuration]);

        $properties = $this->propRepository->findBy([
            'configuration' => $this->configuration
        ], ['key' => 'ASC']);

        $context = [
            'application' => $this->application,
            'configuration' => $this->configuration,
            'properties' => $properties,
            'target' => $target
        ];

        $this->template->render($context);
    }
}
