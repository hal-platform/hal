<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers;

use QL\Hal\Services\StatsService;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Response;

class LoginController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type Response
     */
    private $response;

    /**
     * @type StatsService
     */
    private $stats;

    /**
     * @param TemplateInterface $template
     * @param Response $response
     * @param StatsService $stats
     */
    public function __construct(TemplateInterface $template, Response $response, StatsService $stats)
    {
        $this->template = $template;
        $this->response = $response;
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

        $rendered = $this->template->render($context);

        $this->response->setBody($rendered);
    }
}
