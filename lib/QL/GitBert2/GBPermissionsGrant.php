<?php
namespace QL\GitBert2;

use Slim\Http\Response;
use Slim\Http\Request;
use Twig_Template;
use QL\GitBert2\Services\Repositories;
use QL\GitBert2\Services\Deployments;
use QL\GitBert2\Services\Users;

class GBPermissionsGrant
{
    /**
     * @param Response
     */
    private $response;

    /**
     * @param Request
     */
    private $request;

    /**
     * @param Twig_Template
     */
    private $tpl;

    /**
     * @param Repositories
     */
    private $repos;

    /**
     * @param Deployments
     */
    private $deployments;

    /**
     * @param Users
     */
    private $users;

    /**
     * @param Response $response
     * @param Twig_Template $tpl
     * @param Services $users
     */
    public function __construct(
        Response $response, 
        Request $request,
        Twig_Template $tpl,
        Repositories $repos,
        Deployments $deployments, 
        Users $users 
        )
    {
        $this->response = $response;
        $this->request = $request;
        $this->tpl = $tpl;
        $this->repos = $repos;
        $this->deployments = $deployments;
        $this->users = $users;
    }

    /**
     * @return null
     */
    public function __invoke()
    {
        $usersList = $this->users->listUsers();
        $deploymentsList = $this->deployments->listDeployments();
        $this->response->body($this->tpl->render([
            'users' => $usersList,
            'deployments' => $deploymentsList
        ]));
    }
}
