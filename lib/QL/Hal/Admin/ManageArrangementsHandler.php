<?php
/*
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Admin;

use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;
use QL\Hal\Services\ArrangementService;

/**
 * @api
 */
class ManageArrangementsHandler
{
    /**
     * @var Response
     */
    private $response;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Twig_Template
     */
    private $tpl;

    /**
     * @var ArrangementService
     */
    private $arr;

    /**
     * @param Response $response
     * @param Request $request
     * @param Twig_Template $tpl
     * @param ArrangementService $arr
     */
    public function __construct(
        Response $response,
        Request $request,
        Twig_Template $tpl,
        ArrangementService $arr
    ) {
        $this->response = $response;
        $this->request = $request;
        $this->tpl  = $tpl;
        $this->arr = $arr;
    }

    public function __invoke()
    {
        $shortName = $this->request->post('shortName');
        $fullName = $this->request->post('fullName');

        if (!$shortName || !$fullName) {
            $this->response->body($this->tpl->render(['error' => "all fields are required"]));
            return;
        }

        $this->arr->create($shortName, $fullName);
        $this->response->status(302);
        $this->response['Location'] = 'http://' . $this->request->getHost() . '/admin/arrangements';
    }
}


