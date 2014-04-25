<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Admin;

use QL\Hal\Services\ServerService;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

/**
 *  Remove an existing server
 *
 *  @author Bridget Schiefer <BridgetSchiefer@quickenloans.com>
 */
class ManageServersRemove
{
    /**
     *  Twig Template
     *
     *  @var Twig_Template
     */
    private $tpl;

    /**
     *
     *  @var ServerService
     */
    private $servers;

    /**
     *  Constructor
     *
     *  @param Twig_Template $tpl
     *  @param ServerService $server
     */
    public function __construct(
        Twig_Template $tpl,
        ServerService $server
    ) {
        $this->tpl = $tpl;
        $this->server = $server;
    }

    /**
     *  @param Request $req
     *  @param Response $res
     *  @param array $params
     *  @param callable $notFound
     *  @return null
     */
    public function __invoke(Request $req, Response $res, array $params = null, callable $notFound = null)
    {
        $id = $params['id'];

        if ($this->validateServer($id)) {
            $this->server->remove($id);
        }

        $res->status(303);
        $res->header('Location', '/admin/servers');
    }

    /**
     *
     *  @param string $id
     *  @return bool
     */
    protected function validateServer($id)
    {
        if ($this->server->getById($id)) {
            return true;
        }

        return false;
    }
}
