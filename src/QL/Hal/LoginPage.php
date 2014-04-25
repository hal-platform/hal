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

class LoginPage
{
    /**
     * @param Twig_Template
     */
    private $tpl;

    /**
     * @param Twig_Template $tpl
     */
    public function __construct(Twig_Template $tpl)
    {
        $this->tpl = $tpl;
    }

    /**
     * @param Request $req
     * @param Response $res
     */
    public function __invoke(Request $req, Response $res)
    {
        $res->body($this->tpl->render([]));
    }
}
