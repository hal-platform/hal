<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Build;

use QL\Hal\Core\Entity\Build;
use QL\Hal\Service\EventLogService;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class BuildController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type Build
     */
    private $build;

    /**
     * @type EventLogService
     */
    private $logService;

    /**
     * @param TemplateInterface $template
     * @param Build $build
     * @param EventLogService $logService
     */
    public function __construct(TemplateInterface $template, Build $build, EventLogService $logService)
    {
        $this->template = $template;

        $this->build = $build;
        $this->logService = $logService;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $this->template->render([
            'build' => $this->build,
            'logs' => $this->logService->getLogs($this->build)
        ]);
    }
}
