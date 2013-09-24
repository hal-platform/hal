<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal;

use Slim\Http\Response;
use Twig_Template;
use QL\Hal\Services\ArrangementService;

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
     * @var array
     */
    private $session;

    /**
     * @var ArrangementService $arrService
     */
    private $arrService;

    /**
     * @param Response $response
     * @param Twig_Template $tpl
     * @param array $session
     * @param ArrangementService $arrService
     */
    public function __construct(Response $response, Twig_Template $tpl, array &$session, ArrangementService $arrService)
    {
        $this->response = $response;
        $this->tpl = $tpl;
        $this->session = &$session;
        $this->arrService = $arrService;
    }

    /**
     * @return null
     */
    public function __invoke()
    {
        $arrangementsList = $this->arrService->listAll();
        $this->response->body($this->tpl->render(['account' => $this->session['account'], 'arrangements' => $arrangementsList]));
    }
}
