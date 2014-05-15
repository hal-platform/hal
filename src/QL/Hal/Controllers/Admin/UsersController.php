<?php

namespace QL\Hal\Controllers\Admin;

use QL\Hal\Core\Entity\Repository\UserRepository;
use Twig_Template;
use Slim\Http\Request;
use Slim\Http\Response;
use QL\Hal\Layout;

/**
 *  Users Controller
 *
 *  @author Matt Colf <matthewcolf@quickenloans.com>
 */
class UsersController
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
     *  @var UserRepository
     */
    private $userRepo;

    /**
     *  @param Twig_Template $template
     *  @param Layout $layout
     *  @param UserRepository $userRepo
     */
    public function __construct(
        Twig_Template $template,
        Layout $layout,
        UserRepository $userRepo
    ) {
        $this->template = $template;
        $this->layout = $layout;
        $this->userRepo = $userRepo;
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
                    'users' => $this->userRepo->findBy([], ['name' => 'ASC'])
                ]
            )
        );
    }
}
