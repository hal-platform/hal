<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\GitBert2;

use QL\GitBert2\Services\DeploymentService;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

class GBDeploymentsHandler
{
    /**
     * @var Response
     */
    private $response;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Twig_Template
     */
    private $tpl;

    /**
     * @var DeploymentService
     */
    private $deployments;

    /**
     * @param Response $response
     * @param Request $request
     * @param Twig_Template $tpl
     * @param DeploymentService $deployments
     */
    public function __construct(
        Response $response,
        Request $request,
        Twig_Template $tpl,
        DeploymentService $deployments
    ) {
        $this->response = $response;
        $this->request = $request;
        $this->tpl = $tpl;
        $this->deployments = $deployments;
    }

    public function __invoke()
    {
        $serverId = $this->request->post('serverId');
        $repoId = $this->request->post('repoId');
        $path = $this->request->post('path');

        if (!$serverId || !$repoId || !$path) {
            $this->response->body($this->tpl->render(['error' => 'all fields are required']));
            return;
        }

        $this->deployments->create($repoId, $serverId, 'Error', 'master', '0000000000000000000000000000000000000000', $path);
        $this->response->status(302);
        $this->response['Location'] = 'http://' . $this->request->getHost() . '/deployments';
    }
}
