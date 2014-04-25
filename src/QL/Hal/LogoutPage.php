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
 *  Logout Page Controller
 */
class LogoutPage
{
    /**
     *  Constructor
     *
     *  @param Session $session
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     *  @param Request $req
     *  @param Response $res
     */
    public function __invoke(Request $req, Response $res)
    {
        $this->session->end();
        $res->redirect($req->getScheme() . '://' . $req->getHostWithPort() . '/', 302);
    }
}
