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
     * @param Twig_Template $tpl
     * @param ArrangementService $arrService
     * @param RepositoryService $repoService
     */
    public function __construct(Twig_Template $tpl, ArrangementService $arrService, RepositoryService $repoService)
    {
        $this->tpl = $tpl;
        $this->arrService = $arrService;
        $this->repoService = $repoService;
    }

    /**
     * @param Request $req
     * @param Response $res
     * @param array|null $params
     * @param callable|null $notFound
     */
    public function __invoke(Request $req, Response $res, array $params = null, callable $notFound = null)
    {
        $shortName = $params['name'];
        $arrId = $this->getArrangementId($shortName);
        if (is_null($arrId)) {
            call_user_func($notFound);
            return;
        }
        $repoList = [];
        if ($arrId) {
            $id = $arrId['ArrangementId']; 
            $repoList = $this->getRepositoriesForArrangement($id);
        }
        $res->body($this->tpl->render(['arrangement' => $shortName, 'repositories' => $repoList]));
    }
    
    private function getArrangementId($shortName) 
    {
        $arrId = $this->arrService->getByShortName($shortName);
        return $arrId;
    }

    private function getRepositoriesForArrangement($arrId)
    {
        $field = "ArrangementId";
        $repoList = $this->repoService->listByField($arrId, $field);
        return $repoList;
    }
}
