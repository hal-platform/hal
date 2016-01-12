<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Kraken\Controller\Configuration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Kraken\ConfigurationDiffService;
use QL\Kraken\Core\Entity\Configuration;
use QL\Kraken\Core\Entity\Target;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class CompareConfigurationController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

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
     * @param Configuration $configuration
     * @param EntityManagerInterface $em
     * @param ConfigurationDiffService $diffService
     */
    public function __construct(
        TemplateInterface $template,
        Configuration $configuration,
        EntityManagerInterface $em,
        ConfigurationDiffService $diffService
    ) {
        $this->template = $template;
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
            'application' => $this->configuration->application(),
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
            'application' => $this->configuration->application(),
            'configuration' => $this->configuration,
            'target' => $target,
            'diffs' => $diffs,
            'is_deployed' => $isDeployed
        ];

        $this->template->render($context);
    }
}
