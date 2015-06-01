<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers;

use QL\Hal\Service\StatsService;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class LoginController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type StatsService
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
