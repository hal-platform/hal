<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers;

use Hal\UI\Service\JobQueueService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\MCP\Common\Time\Clock;
use QL\MCP\Common\Time\TimePoint;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class QueueHistoryController implements ControllerInterface
{
    use TemplatedControllerTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var JobQueueService
     */
    private $queue;

    /**
     * @var Clock
     */
    private $clock;

    /**
     * @var string
     */
    private $timezone;

    /**
     * @param TemplateInterface $template
     * @param JobQueueService $queue
     * @param Clock $clock
     * @param string $timezone
     */
    public function __construct(
        TemplateInterface $template,
        JobQueueService $queue,
        Clock $clock,
        $timezone
    ) {
        $this->template = $template;
        $this->queue = $queue;

        $this->clock = $clock;
        $this->timezone = $timezone;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $date = $request
            ->getAttribute('route')
            ->getArgument('date');

        [$from, $to] = $this->queue->getTimeRange($date, $this->timezone);

        $isToday = $this->queue->isToday($from);

        return $this->withTemplate($request, $response, $this->template, [
            'is_today' => $isToday,
            'selected_date' => $from,
            'pending' => $this->queue->getHistory($from, $to)
        ]);
    }
}
