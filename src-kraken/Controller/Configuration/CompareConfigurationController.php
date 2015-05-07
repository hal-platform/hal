<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller\Configuration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Kraken\ConfigurationDiffService;
use QL\Kraken\Entity\Application;
use QL\Kraken\Entity\Configuration;
use QL\Kraken\Entity\ConfigurationProperty;
use QL\Kraken\Entity\Target;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class CompareConfigurationController implements ControllerInterface
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
    private $targetRepo;
    private $propertyRepo;

    /**
     * @type ConfigurationDiffService
     */
    private $diffService;

    /**
     * @param TemplateInterface $template
     * @param Application $application
     * @param Configuration $configuration
     * @param EntityManagerInterface $em
     * @param ConfigurationDiffService $diffService
     */
    public function __construct(
        TemplateInterface $template,
        Application $application,
        Configuration $configuration,
        EntityManagerInterface $em,
        ConfigurationDiffService $diffService
    ) {
        $this->template = $template;
        $this->application = $application;
        $this->configuration = $configuration;

        $this->diffService = $diffService;
        $this->targetRepo = $em->getRepository(Target::CLASS);
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        $target = $this->targetRepo->findOneBy([
            'application' => $this->application,
            'environment' => $this->configuration->environment()
        ]);

        $diffs = [];
        if ($target) {
            $latest = $this->diffService->resolveLatestConfiguration($target->application(), $target->environment());
            $diffs = $this->diffService->diff($this->configuration, $latest);
        }

        $isDeployed = false;
        if ($target && $target->configuration() === $this->configuration) {
            $isDeployed = true;
        }

        $context = [
            'application' => $this->application,
            'configuration' => $this->configuration,
            'target' => $target,
            'diffs' => $diffs,
            'is_deployed' => $isDeployed
        ];

        $this->template->render($context);
    }
}
