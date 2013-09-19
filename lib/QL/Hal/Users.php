<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal;

use QL\Hal\Services\UserService;
use Slim\Http\Response;
use Slim\Slim;
use Twig_Template;

/**
 * @api
 */
class Users
{
    /**
     * @var Response
     */
    private $response;

    /**
     * @var Twig_Template
     */
    private $tpl;

    /**
     * @var UserService
     */
    private $userService;

    /**
     * @param Response $response
     * @param Twig_Template $tpl
     * @param UserService $userService
     */
    public function __construct(Response $response, Twig_Template $tpl, UserService $userService)
    {
        $this->response = $response;
        $this->tpl = $tpl;
        $this->userService = $userService;
    }

    /**
     * @param int $commonId
     * @param Slim $app
     * @return null
     */
    public function __invoke($commonId, Slim $app)
    {
        $user = $this->userService->getById($commonId);
        if (is_null($user)) {
            $app->notFound();
            return;
        }
        $this->response->body($this->tpl->render([
            'user' => $user,
            'total_pushes' => 0,
        ]));
    }
}
