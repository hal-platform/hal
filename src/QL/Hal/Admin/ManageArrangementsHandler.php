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
     * @var Twig_Template
     */
    private $tpl;

    /**
     * @var ArrangementService
     */
    private $arr;

    /**
     * @param Twig_Template $tpl
     * @param ArrangementService $arr
     */
    public function __construct(
        Twig_Template $tpl,
        ArrangementService $arr
    ) {
        $this->tpl  = $tpl;
        $this->arr = $arr;
    }

    public function __invoke(Request $req, Response $res)
    {
        $shortName = $req->post('shortName');
        $fullName = $req->post('fullName');
        $errors = [];
        $data = [];

        $this->validateShortName($shortName, $errors);
        $this->validateFullName($fullName, $errors);

        if ($errors) {
            $data['arrangements'] = $this->arr->listAll();
            $data['cur_sn'] = $shortName;
            $data['cur_fn'] = $fullName;
            $data['errors'] = $errors;
            $res->body($this->tpl->render($data));
            return;
        }

        $this->arr->create(strtolower($shortName), $fullName);
        $res->status(303);
        $res->header('Location', $req->getScheme() . '://' . $req->getHostWithPort() . '/admin/arrangements');
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


