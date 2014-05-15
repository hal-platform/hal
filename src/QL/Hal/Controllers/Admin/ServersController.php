<?php

namespace QL\Hal\Controllers\Admin;

use QL\Hal\Core\Entity\Repository\ServerRepository;
use Twig_Template;
use Slim\Http\Request;
use Slim\Http\Response;
use QL\Hal\Layout;

/**
 *  Servers Controller
 *
 *  @author Matt Colf <matthewcolf@quickenloans.com>
 */
class ServersController
{
    /**
     *  @var Twig_Template
     */
    private $template;

    /**
     *  @var Layout
     */
    private $layout;

    /**
     *  @var ServerRepository
     */
    private $serverRepo;

    /**
     *  @param Twig_Template $template
     *  @param Layout $layout
     *  @param ServerRepository $serverRepo
     */
    public function __construct(
        Twig_Template $template,
        Layout $layout,
        ServerRepository $serverRepo
    ) {
        $this->template = $template;
        $this->layout = $layout;
        $this->serverRepo = $serverRepo;
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
                    "servers" => $this->serverRepo->findAll()
                ]
            )
        );
    }
}
