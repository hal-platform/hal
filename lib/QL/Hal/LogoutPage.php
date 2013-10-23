<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal;

use Slim\Http\Request;
use Slim\Http\Response;

/**
 * @api
 */
class LogoutPage
{
    public function __construct()
    {
        session_start();
    }

    /**
     * @param Request $req
     * @param Response $res
     */
    public function __invoke(Request $req, Response $res)
    {
        session_destroy();
        $res->status(302);
        $res->header('Location', $req->getScheme() . '://' . $req->getHostWithPort() . '/');
    }
}
