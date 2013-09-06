<?php
namespace QL\Hal;

use Slim\Http\Response;
use Twig_Template;
use QL\Hal\Services\Repositories;

class GBPermissions
{
    /**
     * @param Response
     */
    private $response;

    /**
     * @param Twig_Template
     */
    private $tpl;
    
    /**
     * @param Response $response
     * @param Twig_Template $tpl
     * @param Services $repos
     */
    public function __construct(Response $response, Twig_Template $tpl, Repositories $repos)
    {
        $this->response = $response;
        $this->tpl = $tpl;
        $this->repos = $repos;
    }

    /**
     * @return null
     */
    public function __invoke()
    {
        $reposList = $this->repos->listRepos();
        $this->response->body($this->tpl->render(['repositories' => $reposList]));
    }
}
