<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Admin;

use QL\Hal\Services\RepositoryService;
use QL\Hal\Services\UserService;
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

    /**
     * @var UserService
     */
    private $userService;

    /**
     * @param Response $response
     * @param Twig_Template $tpl
     * @param RepositoryService $repoService
     * @param UserService $userService
     */
    public function __construct(Response $response, Twig_Template $tpl, RepositoryService $repoService, UserService $userService)
    {
        $this->response = $response;
        $this->tpl = $tpl;
        $this->repoService = $repoService;
        $this->userService = $userService;
    }

    /**
     * @return null
     */
    public function __invoke()
    {
        $this->response->body($this->tpl->render([
            'total_users' => $this->userService->totalCount(),
            'total_projects' => $this->repoService->totalCount(),
            'total_pushes' => 0,
        ]));
    }
}
