<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Bouncers;

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Exception\Stop;
use QL\Hal\Session;
use QL\Hal\Services\PermissionsService;
use Twig_Environment;
use MCP\Corp\Account\User;

/**
 *  A bouncer that checks to see if the current user is an admin
 *
 *  @author Matt Colf <matthewcolf@quickenloans.com>
 */
class AdminBouncer
{
    /**
     *  @var \QL\Hal\Session
     */
    private $session;

    /**
     * @var PermissionsService
     */
    private $permissions;

    /**
     *  @var \Twig_Environment
     */
    private $twig;

    /**
     *  Constructor
     *
     *  @param Session $session
     *  @param PermissionsService $permissions
     *  @param Twig_Environment $twig
     */
    public function __construct(Session $session, PermissionsService $permissions, Twig_Environment $twig)
    {
        $this->session = $session;
        $this->permissions = $permissions;
        $this->twig = $twig;
    }

    /**
     *  Run the bouncer
     *
     *  @param Request $request
     *  @param Response $response
     *  @throws Stop
     */
    public function __invoke(Request $request, Response $response)
    {
        $account = $this->session->get('account');

        if (!($account instanceof User) || !$this->permissions->allowAdmin($account)) {
            $response->status(403);
            $response->body($this->twig->loadTemplate('denied.twig')->render([]));
            throw new Stop();
        }
    }
}
