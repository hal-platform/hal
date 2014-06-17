<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers;

use QL\Hal\Layout;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

class QueueController
{
    /**
     * @var Twig_Template
     */
    private $template;

    /**
     * @var Layout
     */
    private $layout;

    /**
     * @param Twig_Template $template
     * @param Layout $layout
     */
    public function __construct(
        Twig_Template $template,
        Layout $layout
    ) {
        $this->template = $template;
        $this->layout = $layout;
    }

    /**
     * @param Request $request
     * @param Response $response
     */
    public function __invoke(Request $request, Response $response)
    {
        $rendered = $this->layout->render($this->template, [

        ]);

        $response->body($rendered);
    }
}
