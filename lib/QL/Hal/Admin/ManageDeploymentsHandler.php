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
use QL\Hal\Session;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

/**
 * @api
 */
class ManageDeploymentsHandler
{
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
     *  @var Session
     */
    private $session;

    /**
     *  @param Twig_Template $tpl
     *  @param RepositoryService $repoService
     *  @param ServerService $serverService
     *  @param DeploymentService $deployments
     *  @param Session $session
     */
    public function __construct(
        Twig_Template $tpl,
        RepositoryService $repoService,
        ServerService $serverService,
        DeploymentService $deployments,
        Session $session
    ) {
        $this->tpl = $tpl;
        $this->repoService = $repoService;
        $this->serverService = $serverService;
        $this->deployments = $deployments;
        $this->session = $session;
    }

    /**
     * @param Request $req
     * @param Response $res
     */
    public function __invoke(Request $req, Response $res)
    {
        $serverId = $req->post('serverId');
        $repoId = $req->post('repositoryId');
        $path = $req->post('path');
        $errors = [];

        $this->validateServer($serverId, $errors);
        $this->validateRepo($repoId, $errors);
        $this->validatePath($path, $errors);

        if (!$serverId || !$repoId || !$path) {
            $res->body($this->tpl->render(['error' => 'all fields are required']));
            return;
        }

        $result = $this->deployments->create(
            $repoId,
            $serverId,
            'Error',
            '(no branch)',
            '0000000000000000000000000000000000000000',
            $path
        );

        if ($result == 0) {
            $this->session->addFlash("Unable to add! A deployment with that repository and server combination already exists.");
        }

        $res->status(303);
        $res->header('Location', $req->getScheme() . '://' . $req->getHostWithPort() . '/admin/deployments');
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
