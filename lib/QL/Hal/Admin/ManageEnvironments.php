<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Admin;

use QL\Hal\Services\EnvironmentService;
use Slim\Http\Response;
use Twig_Template;

/**
 * @api
 */
class ManageEnvironments
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
     * @var EnvironmentService
     */
    private $envService;

    /**
     * @param Response $response
     * @param Twig_Template $tpl
     * @param EnvironmentService $envService
     */
    public function __construct(
        Response $response,
        Twig_Template $tpl,
        EnvironmentService $envService
    ) {
        $this->response = $response;
        $this->tpl = $tpl;
        $this->envService = $envService;
    }

    public function __invoke()
    {
        $this->response->body($this->tpl->render([
            'envs' => $this->envService->listAll()
        ]));
    }
}
