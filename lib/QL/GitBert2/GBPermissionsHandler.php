<?php
namespace QL\GitBert2;

use Slim\Http\Response;
use Slim\Http\Request;
use Twig_Template;
use QL\GitBert2\Services\Users;
use QL\GitBert2\Services\Repositories;
use QL\GitBert2\Services\Servers;

class GBPermissionsHandler
{
    private $response;
    private $request;
    private $tpl;
    private $users;
    private $repos;
    private $servers;

    public function __construct(
        Response $response,
        Request $request,
        Twig_Template $tpl,
        Users $users,
        Repositories $repos,
        Servers $servers
    ) {
        $this->response = $response;
        $this->request = $request;
        $this->tpl = $tpl;
        $this->users = $users;
        $this->repos = $repos;
        $this->servers = $servers;
    }

    public function __invoke()
    {
        $userId = $this->request->post('userId');
        $name = $this->request->post('displayName');

        if ($userId) { 
            $this->response->body($this->tpl->render([
                'userId' => $userId,
                'displayName' => $name
            ]));
        }
    }
            

}
