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

/**
 * @api
 */
class ArrangementListPage
{
    /**
     * @var Twig_Template
     */
    private $tpl;

    /**
     * @var ArrangementService $arrService
     */
    private $arrService;

    /**
     * @param Twig_Template $tpl
     * @param ArrangementService $arrService
     */
    public function __construct(Twig_Template $tpl, ArrangementService $arrService)
    {
        $this->tpl = $tpl;
        $this->arrService = $arrService;
    }

    /**
     * @param Request $req
     * @param Response $res
     * @return null
     */
    public function __invoke(Request $req, Response $res)
    {
        $arrangementsList = $this->arrService->listAll();
        $res->body($this->tpl->render(['arrangements' => $arrangementsList]));
    }
}
