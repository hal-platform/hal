<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal;

use Slim\Http\Request;
use Slim\Http\Response;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @api
 */
class LoginGuard
{
    /**
     * @var Session
     */
    private $session;

    /**
     * @var ContainerBuilder
     */
    private $dic;

    /**
     * @param Session $session
     * @param ContainerBuilder $dic
     */
    public function __construct(Session $session, ContainerBuilder $dic)
    {
        $this->session = $session;
        $this->dic = $dic;
    }

    /**
     * @param Request $req
     * @param Response $res
     */
    public function __invoke(Request $req, Response $res)
    {
        if (!$this->session->get('account')) {
            $res->redirect($req->getScheme() . '://' . $req->getHostWithPort() . '/', 302);
        }
        $this->dic->set('currentUserContext', $this->session->get('account'));
    }
}
