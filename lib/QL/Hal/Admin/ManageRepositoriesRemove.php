<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Admin;

use QL\Hal\Services\DeploymentService;
use QL\Hal\Services\RepositoryService;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

/**
 *  Remove an existing deployment
 *
 *  @author Bridget Schiefer <BridgetSchiefer@quickenloans.com>
 */
class ManageRepositoriesRemove
{
    /**
     *  Twig Template
     *
     *  @var Twig_Template
     */
    private $tpl;

    /**
     *  Deployment Service
     *
     *  @var DeploymentService
     */
    private $deployment;

    /**
     *  Repository Service
     *
     *  @var RepositoryService
     */
    private $repositories;

    /**
     *  @param Twig_Template $tpl
     *  @param Service $repositories
     */
    public function __construct(
        Twig_Template $tpl,
        DeploymentService $deployment,
        RepositoryService $repositories
    ) {
        $this->tpl = $tpl;
        $this->deployment = $deployment;
        $this->repositories = $repositories;
    }

    /**
     *  @param Request $req
     *  @param Response $res
     *  @param array $params
     *  @param callable $notFound
     *  @return null
     */
    public function __invoke(Request $req, Response $res, array $params = null, callable $notFound = null)
    {
        $id = $params['id'];

        if ($this->validateRepo($id)) {
            if ($this->validateNoDeployment($id)) {
                $this->repositories->remove($id);
            }
        }

        $res->status(303);
        $res->header('Location', '/admin/repositories');
    }

    /**
     *  Check if a repository ID exists
     *  @param string $repoId
     *  @return bool
     */
    protected function validateRepo($id)
    {
        if ($this->repositories->getById($id)) {
            return true;
        }

        return false;
    }

    /**
     *  Check if a deployment ID exists
     *  @param string $id
     *  @return bool
     */
    protected function validateNoDeployment($id)
    {
        $results = $this->deployment->listAllByRepoId($id);
        if ($results) {
            return false;
        } else {
            return true;
        }
    }
}
