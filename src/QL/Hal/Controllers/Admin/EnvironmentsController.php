<?php

namespace QL\Hal\Controllers\Admin;

use QL\Hal\Core\Entity\Repository\EnvironmentRepository;
use Twig_Template;
use Slim\Http\Request;
use Slim\Http\Response;
use QL\Hal\Layout;

/**
 *  Environments Controller
 *
 *  @author Matt Colf <matthewcolf@quickenloans.com>
 */
class EnvironmentsController
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
     *  @var EnvironmentRepository
     */
    private $envRepo;

    /**
     *  @param Twig_Template $template
     *  @param Layout $layout
     *  @param EnvironmentRepository $envRepo
     */
    public function __construct(
        Twig_Template $template,
        Layout $layout,
        EnvironmentRepository $envRepo
    ) {
        $this->template = $template;
        $this->layout = $layout;
        $this->envRepo = $envRepo;
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
                    'envs' => $this->envRepo->findBy([], ['order' => 'ASC'])
                ]
            )
        );
    }
}
