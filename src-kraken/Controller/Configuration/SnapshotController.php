<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller\Configuration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Kraken\Entity\Application;
use QL\Kraken\Entity\Configuration;
use QL\Kraken\Entity\ConfigurationProperty;
use QL\Kraken\Entity\Target;
use QL\Kraken\Service\ConsulService;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class SnapshotController implements ControllerInterface
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
     * @type ConsulService
     */
    private $consul;

    /**
     * @type EntityRepository
     */
    private $targetRepo;
    private $configurationPropertyRepo;

    /**
     * @param TemplateInterface $template
     * @param Application $application
     * @param Configuration $configuration
     * @param ConsulService $consul
     * @param EntityManagerInterface $em
     */
    public function __construct(
        TemplateInterface $template,
        Application $application,
        Configuration $configuration,
        ConsulService $consul,
        EntityManagerInterface $em
    ) {
        $this->template = $template;
        $this->application = $application;
        $this->configuration = $configuration;

        $this->consul = $consul;
        $this->targetRepo = $em->getRepository(Target::CLASS);
        $this->configurationPropertyRepo = $em->getRepository(ConfigurationProperty::CLASS);
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        $target = $this->targetRepo->findOneBy(['configuration' => $this->configuration]);

        $properties = $this->configurationPropertyRepo->findBy([
            'configuration' => $this->configuration
        ], ['key' => 'ASC']);

        $isDeployed = ($target->configuration()->id() === $this->configuration->id());

        $checksums = ($isDeployed) ? $this->getChecksums($target) : [];

        $context = [
            'application' => $this->application,
            'configuration' => $this->configuration,

            'properties' => $properties,
            'target' => $target,

            'is_deployed' => $isDeployed,
            'checksums' => $checksums
        ];

        $this->template->render($context);
    }

    /**
     * @param Target $target
     *
     * @return array
     */
    private function getChecksums(Target $target)
    {
        $checksums = $this->consul->getChecksums($target);

        if ($checksums === null) {
            return [];
        }

        return $checksums;
    }
}
