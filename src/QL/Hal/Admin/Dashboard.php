<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Admin;

use QL\Hal\Layout;
use QL\Hal\Services\RepositoryService;
use QL\Hal\Services\UserService;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

/**
 * @api
 */
class Dashboard
{
    /**
     * @var Response
     */
    private $response;
    /**
     * @var Twig_Template
     */
    private $tpl;

    private $repoService;

    private $layout;

    /**
     * @var UserService
     */
    private $userService;

    /**
     * @param Twig_Template $tpl
     * @param RepositoryService $repoService
     * @param UserService $userService
     * @param Layout $layout
     */
    public function __construct(Twig_Template $tpl, RepositoryService $repoService, UserService $userService, Layout $layout)
    {
        $this->tpl = $tpl;
        $this->repoService = $repoService;
        $this->userService = $userService;
        $this->layout = $layout;
    }

    /**
     * @param Request $req
     * @param Response $res
     * @return null
     */
    public function __invoke(Request $req, Response $res)
    {
        $data = [
            'total_users' => $this->userService->totalCount(),
            'total_projects' => $this->repoService->totalCount(),
            'total_pushes' => 0,
            'isAdmin' => true,
        ];
        $res->body($this->layout->renderTemplateWithLayoutData($this->tpl, $data));
    }
}
