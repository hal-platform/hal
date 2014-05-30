<?php

namespace QL\Hal\Controllers\Group;

use Twig_Template;
use Slim\Http\Request;
use Slim\Http\Response;
use QL\Hal\Layout;
use QL\Hal\Core\Entity\Repository\GroupRepository;

/**
 *  Groups Controller
 *
 *  @author Matt Colf <matthewcolf@quickenloans.com>
 */
class GroupsController
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
     *  @var GroupRepository
     */
    private $groupRepo;

    /**
     *  @param Twig_Template $template
     *  @param Layout $layout
     *  @param GroupRepository $groupRepo
     */
    public function __construct(
        Twig_Template $template,
        Layout $layout,
        GroupRepository $groupRepo
    ) {
        $this->template = $template;
        $this->layout = $layout;
        $this->groupRepo = $groupRepo;
    }

    /**
     *  Run the controller
     *
     *  @param Request $request
     *  @param Response $response
     */
    public function __invoke(Request $request, Response $response)
    {
        $rendered = $this->layout->render($this->template, [
            'groups' => $this->groupRepo->findAll()
        ]);

        $response->body($rendered);
    }
}
