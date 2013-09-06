<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Admin;

use QL\Hal\Services\RepositoryService;
use Slim\Http\Response;
use Twig_Template;

class ManageRepositories
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
