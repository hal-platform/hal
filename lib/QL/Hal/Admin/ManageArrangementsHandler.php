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
        $errors = [];
        $data = [];

        $this->validateShortName($shortName, $errors);
        $this->validateFullName($fullName, $errors);

        if ($errors) {
            $data['arrangements'] = $this->arr->listAll();
            $data['cur_sn'] = $shortName;
            $data['cur_fn'] = $fullName;
            $data['errors'] = $errors;
            $this->response->body($this->tpl->render($data));
            return;
        }

        $this->arr->create(strtolower($shortName), $fullName);
        $this->response->status(303);
        $this->response['Location'] = 'http://' . $this->request->getHost() . '/admin/arrangements';
    }

    private function validateShortName($shortName, array &$errors)
    {
        if (!$shortName) {
            $errors[] = 'Short name must be specified';
        }

        if (!preg_match('@^[a-z0-9_-]*$@', strtolower($shortName))) {
            $errors[] = 'Short name must be be composed of alphanumeric, underscore and/or hyphen characters';
        }

        if ($shortName > 24) {
            $errors[] = 'Short name must be under 24 characters';
        }
    }

    private function validateFullName($fullName, array &$errors)
    {
        if (!$fullName) {
            $errors[] = 'Full name must be specified';
        }

        if (!mb_check_encoding($fullName, 'UTF-8')) {
            $errors[] = 'Full name must be valid UTF-8';
        }

        if (mb_strlen($fullName, 'UTF-8') > 48) {
            $errors[] = 'Full name must be 48 characters or under';
        }
    }
}


