<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Admin;

use QL\Hal\Services\DeploymentService;
use QL\Hal\Services\RepositoryService;
use QL\Hal\Services\ServerService;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

/**
 * @api
 */
class ManageDeploymentsHandler
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
     * @var ServerService
     */
    private $serverService;

    /**
     * @var RepositoryService
     */
    private $repoService;

    /**
     * @var DeploymentService
     */
    private $deployments;

    /**
     * @param Response $response
     * @param Request $request
     * @param Twig_Template $tpl
     * @param RepositoryService $repoService
     * @param ServerService $serverService
     * @param DeploymentService $deployments
     */
    public function __construct(
        Response $response,
        Request $request,
        Twig_Template $tpl,
        RepositoryService $repoService,
        ServerService $serverService,
        DeploymentService $deployments
    ) {
        $this->response = $response;
        $this->request = $request;
        $this->tpl = $tpl;
        $this->repoService = $repoService;
        $this->serverService = $serverService;
        $this->deployments = $deployments;
    }

    public function __invoke()
    {
        $serverId = $this->request->post('serverId');
        $repoId = $this->request->post('repositoryId');
        $path = $this->request->post('path');
        $errors = [];

        $this->validateServer($serverId, $errors);
        $this->validateRepo($repoId, $errors);
        $this->validatePath($path, $errors);

        if (!$serverId || !$repoId || !$path) {
            $this->response->body($this->tpl->render(['error' => 'all fields are required']));
            return;
        }

        $this->deployments->create($repoId, $serverId, 'Error', '(no branch)', '0000000000000000000000000000000000000000', $path);
        $this->response->status(303);
        $this->response['Location'] = 'http://' . $this->request->getHost() . '/admin/deployments';
    }

    /**
     * @param int $serverId
     * @param array $errors
     */
    private function validateServer($serverId, array &$errors)
    {
        if (!$this->serverService->getById($serverId)) {
            $errors[] = 'Given server id does not exist';
        }
    }

    /**
     * @param int $repoId
     * @param array $errors
     */
    private function validateRepo($repoId, array &$errors)
    {
        if (!$this->repoService->getById($repoId)) {
            $errors[] = 'Given repository id does not exist';
        }
    }

    /**
     * @param string $path
     * @param array $errors
     */
    private function validatePath($path, array &$errors)
    {
        if (strlen($path) > 255) {
            $errors[] = 'Target Path must be under 255 bytes long';
        }

        if (!$path) {
            $errors[] = 'Target Path is required';
        }
    }
}
