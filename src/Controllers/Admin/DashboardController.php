<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Admin;

use QL\Panthor\TemplateInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class DashboardController
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type int
     */
    private $statusCode;

    /**
     * @param TemplateInterface $template
     * @param int $statusCode
     */
    public function __construct(TemplateInterface $template, $statusCode = 200)
    {
        $this->template = $template;
        $this->statusCode = $statusCode;
    }

    /**
     * @param Request $request
     * @param Response $response
     */
    public function __invoke(Request $request, Response $response)
    {
        $rendered = $this->template->render();

        $response->setStatus($this->statusCode);
        $response->setBody($rendered);
    }
}
