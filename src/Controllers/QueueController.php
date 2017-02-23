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
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class QueueController implements ControllerInterface
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
     * @param TemplateInterface $template
     * @param JobQueueService $queue
     */
    public function __construct(TemplateInterface $template, JobQueueService $queue)
    {
        $this->template = $template;
        $this->queue = $queue;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        return $this->withTemplate($request, $response, $this->template, [
            'pending' => $this->queue->getPendingJobs()
        ]);
    }
}
