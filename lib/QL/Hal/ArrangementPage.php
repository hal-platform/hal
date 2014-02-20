<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal;

use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;
use QL\Hal\Services\ArrangementService;
use QL\Hal\Services\RepositoryService;

/**
 * @api
 */
class ArrangementPage
{
    /**
     * @var Response
     */
    private $response;
    /**
     * @var Twig_Template
     */
    private $tpl;

    /**
     * @var ArrangementService $arrService
     */
    private $arrService;

    /**
     * @var RepositoryService $repoService
     */
    private $repoService;

    /**
     * @var Layout
     */
    private $layout;

    /**
     * @param Twig_Template $tpl
     * @param ArrangementService $arrService
     * @param RepositoryService $repoService
     * @param Layout $layout
     */
    public function __construct(
        Twig_Template $tpl,
        ArrangementService $arrService,
        RepositoryService $repoService,
        Layout $layout
    ) {
        $this->tpl = $tpl;
        $this->arrService = $arrService;
        $this->repoService = $repoService;
        $this->layout = $layout;
    }

    /**
     *  @param Request $req
     *  @param Response $res
     *  @param array|null $params
     *  @param callable|null $notFound
     */
    public function __invoke(Request $req, Response $res, array $params = null, callable $notFound = null)
    {
        $arr = $this->arrService->getByShortName($params['name']);

        if (is_null($arr)) {
            call_user_func($notFound);
            return;
        }

        $repos = [];
        if ($arr) {
            $id = $arr['ArrangementId'];
            $repos = $this->getRepositoriesForArrangement($id);
        }

        $data = ['arrangement' => $arr, 'repositories' => $repos];
        $res->body($this->layout->renderTemplateWithLayoutData($this->tpl, $data));
    }

    /**
     *  Get all repositories for a given arrangement short name
     *
     *  @param $short
     *  @return array
     */
    private function getRepositoriesForArrangement($short)
    {
        $field = "ArrangementId";
        $repoList = $this->repoService->listByField($short, $field);
        return $repoList;
    }
}
