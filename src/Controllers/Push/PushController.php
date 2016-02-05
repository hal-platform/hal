<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Controllers\Push;

use QL\Hal\Core\Entity\Push;
use QL\Hal\Service\EventLogService;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class PushController implements ControllerInterface
{
    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var Push
     */
    private $push;

    /**
     * @var EventLogService
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
        // Resolves logs from redis (for in progress jobs) or db (after completed)
        $logs = $this->logService->getLogs($this->push);

        $this->template->render([
            'push' => $this->push,
            'logs' => $logs
        ]);
    }
}
