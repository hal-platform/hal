<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller\Configuration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Flasher;
use QL\Kraken\ConfigurationDiffService;
use QL\Kraken\Core\Entity\Configuration;
use QL\Kraken\Core\Entity\Target;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class RollbackController implements ControllerInterface
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
     * @type ConfigurationDiffService
     */
    private $diffService;

    /**
     * @type Flasher
     */
    private $flasher;

    /**
     * @type EntityRepository
     */
    private $targetRepo;

    /**
     * @param TemplateInterface $template
     * @param Configuration $configuration
     * @param ConfigurationDiffService $diffService
     * @param Flasher $flasher
     * @param EntityManagerInterface $em
     */
    public function __construct(
        TemplateInterface $template,
        Configuration $configuration,
        ConfigurationDiffService $diffService,
        Flasher $flasher,
        EntityManagerInterface $em
    ) {
        $this->template = $template;
        $this->configuration = $configuration;
        $this->diffService = $diffService;
        $this->flasher = $flasher;

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

        if (!$target) {
            return $this->flasher
                ->withFlash('Cannot rollback to this configuration. The deployment target seems to have been removed.', 'error')
                ->load('kraken.configuration', ['configuration' => $this->configuration->id()]);
        }

        // Build up the Diffs from the old snapshot
        $diffs = $this->diffService->resolveConfiguration($this->configuration);

        // Compare old snapshot to active configuration
        // Only if there is an active configuration, and its not the same as what we're trying to redeploy
        if ($target->configuration() && $target->configuration() !== $this->configuration) {
            $diffs = $this->diffService->diff($target->configuration(), $diffs);
        }

        $context = [
            'configuration' => $this->configuration,
            'application' => $this->configuration->application(),
            'environment' => $this->configuration->environment(),
            'target' => $target,
            'diffs' => $diffs,
        ];

        $this->template->render($context);
    }
}
