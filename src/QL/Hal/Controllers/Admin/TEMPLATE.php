<?php

namespace QL\Hal\Controllers\Admin;

use Twig_Template;
use Slim\Http\Request;
use Slim\Http\Response;
use QL\Hal\Layout;

/**
 *  Repositories Controller
 *
 *  @author Matt Colf <matthewcolf@quickenloans.com>
 */
class TEMPLATE
{
    /**
     *  @var Twig_Template
     */
    private $template;

    /**
     *  @var Layout
     */
    private $layout;

    public function __construct(
        Twig_Template $template,
        Layout $layout
    ) {
        $this->template = $template;
        $this->layout = $layout;
    }

    /**
     *  Run the controller
     *
     *  @param Request $request
     *  @param Response $response
     *  @param array $params
     */
    public function __invoke(Request $request, Response $response, array $params = [])
    {

        $response->body(
            $this->layout->render(
                $this->template,
                [

                ]
            )
        );
    }
}
