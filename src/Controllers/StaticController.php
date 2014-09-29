<?php

namespace QL\Hal\Controllers;

use Twig_Template;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Render a twig template and do nothing else.
 */
class StaticController
{
    /**
     *  @var Twig_Template
     */
    private $template;

    /**
     *  @var int
     */
    private $statusCode;

    /**
     *  @param Twig_Template $template
     *  @param int $statusCode
     */
    public function __construct(Twig_Template $template, $statusCode = 200)
    {
        $this->template = $template;
        $this->statusCode = $statusCode;
    }

    /**
     *  @param Request $request
     *  @param Response $response
     */
    public function __invoke(Request $request, Response $response)
    {
        $rendered = $this->template->render([]);

        $response->setStatus($this->statusCode);
        $response->body($rendered);
    }
}
