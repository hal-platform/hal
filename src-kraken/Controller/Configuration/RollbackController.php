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
use QL\Kraken\Entity\Configuration;
use QL\Kraken\Entity\Target;
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
     * @type EntityRepository
     */
    private $targetRepo;

    /**
     * @param TemplateInterface $template
     * @param Configuration $configuration
     * @param ConfigurationDiffService $diffService
     * @param EntityManagerInterface $em
     */
    public function __construct(
        TemplateInterface $template,
        Configuration $configuration,
        ConfigurationDiffService $diffService,
        EntityManagerInterface $em
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
        // $diffs = $this->diffService->resolveLatestConfiguration($this->target->application(), $this->target->environment());

        // // Add "Deployed" configuration
        // if ($this->target->configuration()) {
        //     // @todo verify checksum against consul checksum
        //     $diffs = $this->diffService->diff($this->target->configuration(), $diffs);
        // }

        $context = [
            'configuration' => $this->configuration,
            // 'application' => $this->target->application(),
            // 'environment' => $this->target->environment(),
            // 'diffs' => $diffs
        ];

        $this->template->render($context);
    }
}
