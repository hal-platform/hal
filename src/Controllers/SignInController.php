<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers;

use Hal\UI\Service\StatsService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class SignInController implements ControllerInterface
{
    use TemplatedControllerTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var StatsService
     */
    private $stats;

    /**
     * @param TemplateInterface $template
     * @param StatsService $stats
     */
    public function __construct(TemplateInterface $template, StatsService $stats)
    {
        $this->template = $template;
        $this->stats = $stats;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        return $this->withTemplate($request, $response, $this->template, [
            'stats' => $this->stats->getStatsForToday()
        ]);
    }
}
