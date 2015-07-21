<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Push;

use QL\Hal\Core\Entity\Push;
use QL\Hal\Service\EventLogService;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class PushController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type Push
     */
    private $push;

    /**
     * @type EventLogService
     */
    private $logService;

    /**
     * @param TemplateInterface $template
     * @param Push $push
     * @param EventLogService $logService
     */
    public function __construct(TemplateInterface $template, Push $push, EventLogService $logService)
    {
        $this->template = $template;

        $this->push = $push;
        $this->logService = $logService;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $this->template->render([
            'push' => $this->push,
            'logs' => $this->logService->getLogs($this->push)
        ]);
    }
}
