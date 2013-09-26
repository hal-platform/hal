<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal;

use Slim\Slim;
use Slim\Http\Response;
use Twig_Template;
use QL\Hal\Services\ArrangementService;
use QL\Hal\Services\RepositoryService;

/**
 * @api
 */
class Arrangements
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
     * @param Response $response
     * @param Twig_Template $tpl
     * @param ArrangementService $arrService
     * @param RepositoryService $repoService
     */
    public function __construct(Response $response, Twig_Template $tpl, ArrangementService $arrService, RepositoryService $repoService)
    {
        $this->response = $response;
        $this->tpl = $tpl;
        $this->arrService = $arrService;
        $this->repoService = $repoService;
    }

    /**
     * @param string $shortName
     * @param Slim $app
     * @return null
     */
    public function __invoke($shortName, Slim $app)
    {
        $arrId = $this->getArrangementId($shortName);
        if (is_null($arrId)) {
            $app->notFound();
            return;
        }
        if ($arrId) {
            $id = $arrId['ArrangementId']; 
            $repoList = $this->getRepositoriesForArrangement($id);
        }
        $this->response->body($this->tpl->render(['repositories' => $repoList]));
    }
    
    private function getArrangementId($shortName) 
    {
        $arrId = $this->arrService->getIdByShortName($shortName);
        return $arrId;
    }

    private function getRepositoriesForArrangement($arrId)
    {
        $repoList = $this->repoService->listByArrangement($arrId);
        return $repoList;
    }

}
