<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Admin;

use QL\Hal\Services\EnvironmentService;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

/**
 * @api
 */
class ManageEnvironments
{
    /**
     * @var Twig_Template
     */
    private $tpl;

    /**
     * @var EnvironmentService
     */
    private $envService;

    /**
     * @param Twig_Template $tpl
     * @param EnvironmentService $envService
     */
    public function __construct(
        Twig_Template $tpl,
        EnvironmentService $envService
    ) {
        $this->tpl = $tpl;
        $this->envService = $envService;
    }

    /**
     * @param Request $req
     * @param Response $res
     */
    public function __invoke(Request $req, Response $res)
    {
        $res->body($this->tpl->render([
            'envs' => $this->envService->listAll()
        ]));
    }
}
