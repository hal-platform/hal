<?php
namespace QL\GitBert2;

use QL\GitBert2\Services\RepositoryService;
use Slim\Http\Response;
use Twig_Template;

class GBRepositories 
{
    /**
     * @param Response
     */
    private $response;

    /**
     * @param Twig_TemplateInterface
     */
    private $tpl;

    /**
     * @param RepositoryService
     */
    private $repos;

    /**
     * @param Response $response
     * @param Twig_Template $tpl
     * @param RepositoryService $repos
     */
    public function __construct(Response $response, Twig_Template $tpl, RepositoryService $repos)
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
        $reposList = $this->repos->listAll();
        $this->response->body($this->tpl->render(['repositories' => $reposList]));
    }
}
