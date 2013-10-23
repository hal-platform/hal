<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal;

use Slim\Http\Request;
use Slim\Http\Response;

class LoginGuard
{
    private $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    public function __invoke(Request $req, Response $res)
    {
        if (!$this->session->get('commonid')) {
            $res->status(302);
            $res->header('Location', $req->getScheme() . '://' . $req->getHostWithPort() . '/');
        }
    }
}
