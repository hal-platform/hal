<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller\Configuration;

use QL\Kraken\ConfigurationDiffService;
use QL\Kraken\Entity\Target;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class DeployController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type Target
     */
    private $target;

    /**
     * @type ConfigurationDiffService
     */
    private $diffService;

    /**
     * @param TemplateInterface $template
     * @param Target $target
     * @param ConfigurationDiffService $diffService
     */
    public function __construct(
        TemplateInterface $template,
        Target $target,
        ConfigurationDiffService $diffService
    ) {
        $this->template = $template;
        $this->target = $target;
        $this->diffService = $diffService;
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        $diffs = $this->diffService->resolveLatestConfiguration($this->target->application(), $this->target->environment());

        // Add "Deployed" configuration
        if ($this->target->configuration()) {
            // @todo verify checksum against consul checksum
            $diffs = $this->diffService->diff($this->target->configuration(), $diffs);
        }

        $context = [
            'target' => $this->target,
            'application' => $this->target->application(),
            'environment' => $this->target->environment(),
            'diffs' => $diffs
        ];

        $this->template->render($context);
    }
}
