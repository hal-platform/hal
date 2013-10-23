<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal;

use QL\Hal\Services\UserService;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Slim;
use Twig_Template;

/**
 * @api
 */
class Users
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
    public function __construct(Twig_Template $tpl, UserService $userService)
    {
        $this->tpl = $tpl;
        $this->userService = $userService;
    }

    /**
     * @param Request $req
     * @param \Slim\Http\Response $res
     * @param array $params
     * @param callable $notFound
     * @return null
     */
    public function __invoke(Request $req, Response $res, array $params = null, callable $notFound = null)
    {
        $commonId = $params['id'];
        $user = $this->userService->getById($commonId);
        if (is_null($user)) {
            call_user_func($notFound);
            return;
        }
        $res->body($this->tpl->render([
            'user' => $user,
            'total_pushes' => 0,
        ]));
    }
}
