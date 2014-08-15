<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\User;

use QL\Hal\Core\Entity\Repository\UserRepository;
use QL\Hal\Layout;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

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
     *  @param Request $request
     *  @param Response $response
     */
    public function __invoke(Request $request, Response $response)
    {
        $rendered = $this->layout->render($this->template, [
            'users' => $this->userRepo->findBy(['isActive' => true], ['name' => 'ASC'])
        ]);

        $response->body($rendered);
    }
}
