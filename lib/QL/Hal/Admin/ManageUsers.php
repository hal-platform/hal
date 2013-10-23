<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Admin;

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
     * @param Twig_Template $tpl
     * @param UserService $userService
     */
    public function __construct(
        Twig_Template $tpl,
        UserService $userService
    )
    {
        $this->tpl = $tpl;
        $this->userService = $userService;
    }

    public function __invoke(Request $req, Response $res)
    {
        $res->body($this->tpl->render(['users' => $this->userService->listAll()]));
    }
}
