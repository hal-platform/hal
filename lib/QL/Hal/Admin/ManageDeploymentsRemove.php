<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Admin;

use QL\Hal\Services\DeploymentService;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

/**
 *  Remove an existing deployment
 *
 *  @author Matt Colf <matthewcolf@quickenloans.com>
 */
class ManageDeploymentsRemove
{
    /**
     *  Twig Template
     *
     *  @var Twig_Template
     */
    private $tpl;

    /**
     *  Deployments Service
     *
     *  @var DeploymentService
     */
    private $deployments;

    /**
     *  Constructor
     *
     *  @param Twig_Template $tpl
     *  @param DeploymentService $deployments
     */
    public function __construct(
        Twig_Template $tpl,
        DeploymentService $deployments
    ) {
        $this->tpl = $tpl;
        $this->deployments = $deployments;
    }

    /**
     *  Remove an existing deployment
     *z
     *  @param Request $req
     *  @param Response $res
     *  @param array $params
     *  @param callable $notFound
     *  @return null
     */
    public function __invoke(Request $req, Response $res, array $params = null, callable $notFound = null)
    {
        $id = $params['id'];

        if ($this->validateDeployment($id)) {
            $this->deployments->remove($id);
        }

        $res->status(303);
        $res->header('Location', '/admin/deployments');
    }

    /**
     *  Check if a deployment ID exists
     *
     *  @param string $id
     *  @return bool
     */
    protected function validateDeployment($id)
    {
        if ($this->deployments->getById($id)) {
            return true;
        }

        return false;
    }
}
