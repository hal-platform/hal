<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Admin;

use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;
use QL\Hal\Services\RepositoryService;
use QL\Hal\Services\ArrangementService;

/**
 * @api
 */
class ManageRepositories
{
    /**
     * @param Twig_Template
     */
    private $tpl;

    /**
     * @param RepositoryService
     */
    private $repos;

    /**
     * @param ArrangementService
     */
    private $arrs;

    /**
     * @param Twig_Template $tpl
     * @param RepositoryService $repos
     * @param ArrangementService $arrs
     */
    public function __construct(Twig_Template $tpl, RepositoryService $repos, ArrangementService $arrs)
    {
        $this->tpl = $tpl;
        $this->repos = $repos;
        $this->arrs = $arrs;
    }

    /**
     * @param Request $req
     * @param Response $res
     * @return null
     */
    public function __invoke(Request $req, Response $res)
    {
        $reposList = $this->repos->listAll();
        $arrsList = $this->arrs->listAll();
        $res->body($this->tpl->render([
            'repositories' => $reposList,
            'arrangements' => $arrsList,
        ]));
    }
}
