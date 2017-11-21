<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Release;

use Hal\Core\Entity\Release;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Service\EventLogService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class ReleaseController implements ControllerInterface
{
    use TemplatedControllerTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var Release
     */
    private $release;

    /**
     * @var EventLogService
     */
    private $logService;

    /**
     * @param TemplateInterface $template
     * @param EventLogService $logService
     */
    public function __construct(TemplateInterface $template, EventLogService $logService)
    {
        $this->template = $template;
        $this->logService = $logService;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $release = $request->getAttribute(Release::class);

        // Resolves logs from redis (for in progress jobs) or db (after completed)
        $logs = $this->logService->getLogs($release);

        return $this->withTemplate($request, $response, $this->template, [
            'release' => $release,
            'logs' => $logs
        ]);
    }
}
