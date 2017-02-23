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

        [$from, $to] = $this->getTimeRange($date);

        $now = $this->clock->read();
        $isToday = $now->format('Y-m-d', 'UTC') === $from->format('Y-m-d', 'UTC');

        return $this->withTemplate($request, $response, $this->template, [
            'is_today' => $isToday,
            'selected_date' => $from,
            'pending' => $this->queue->getHistory($from, $to)
        ]);
    }

    /**
     * @param string|null $date
     *
     * @return array
     */
    private function getTimeRange($date = '')
    {
        if ($date) {
            $date = $this->clock->fromString($date, 'Y-m-d');
        }

        if (!$date) {
            $date = $this->clock->read();
        }

        $y = $date->format('Y', $this->timezone);
        $m = $date->format('m', $this->timezone);
        $d = $date->format('d', $this->timezone);

        $from = new TimePoint($y, $m, $d, 0, 0, 0, $this->timezone);
        $to = new TimePoint($y, $m, $d, 23, 59, 59, $this->timezone);

        return [$from, $to];
    }
}
