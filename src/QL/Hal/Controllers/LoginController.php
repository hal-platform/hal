<?php

namespace QL\Hal\Controllers;

use Twig_Template;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 *  Login Page Controller
 *
 *  @author Matt Colf <matthewcolf@quickenloans.com>
 */
class LoginController
{
    /**
     *  @var Twig_Template
     */
    private $template;

    /**
     *  @param Twig_Template $template
     */
    public function __construct(
        Twig_Template $template
    ) {
        $this->template = $template;
    }

    /**
     *  @param Request $request
     *  @param Response $response
     */
    public function __invoke(Request $request, Response $response)
    {
        $response->body(
            $this->template->render(
                []
            )
        );
    }
}
