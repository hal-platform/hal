<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers;

use Hal\UI\Service\StatsService;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class LoginController implements ControllerInterface
{
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
     * @param Response $response
     * @param StatsService $stats
     */
    public function __construct(TemplateInterface $template, StatsService $stats)
    {
        $this->template = $template;
        $this->stats = $stats;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $context = [
            'stats' => $this->stats->getStatsForToday()
        ];

        $this->template->render($context);
    }
}
