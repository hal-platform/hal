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
