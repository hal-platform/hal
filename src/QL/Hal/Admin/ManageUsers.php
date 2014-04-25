<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Admin;

use QL\Hal\Layout;
use QL\Hal\Services\UserService;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

/**
 * @api
 */
class ManageUsers
{
    /**
     * @var Twig_Template
     */
    private $tpl;
    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var Layout
     */
    private $layout;

    /**
     * @param Twig_Template $tpl
     * @param UserService $userService
     * @param Layout $layout
     */
    public function __construct(
        Twig_Template $tpl,
        UserService $userService,
        Layout $layout
    )
    {
        $this->tpl = $tpl;
        $this->userService = $userService;
        $this->layout = $layout;
    }

    public function __invoke(Request $req, Response $res)
    {
        $context = ['users' => $this->userService->listAll()];
        $res->body($this->layout->renderTemplateWithLayoutData($this->tpl, $context));
    }
}
