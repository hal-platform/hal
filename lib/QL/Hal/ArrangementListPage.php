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
     * @var Layout
     */
    private $layout;

    /**
     * @param Twig_Template $tpl
     * @param ArrangementService $arrService
     * @param Layout $layout
     */
    public function __construct(
        Twig_Template $tpl,
        ArrangementService $arrService,
        Layout $layout
    ) {
        $this->tpl = $tpl;
        $this->arrService = $arrService;
        $this->layout = $layout;
    }

    /**
     * @param Request $req
     * @param Response $res
     * @return null
     */
    public function __invoke(Request $req, Response $res)
    {
        $arrangementsList = $this->arrService->listAll();
        $data = ['arrangements' => $arrangementsList];
        $res->body($this->layout->renderTemplateWithLayoutData($this->tpl, $data));
    }
}
