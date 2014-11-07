<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Bouncers;

use QL\Hal\Services\PermissionsService;
use QL\Hal\Session;
use Slim\Exception\Stop;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Environment;
use MCP\Corp\Account\User;

/**
 * A bouncer that checks to see if the current user is an admin
 */
class AdminBouncer
{
    /**
     * @var Session
     */
    private $session;

    /**
     * @var PermissionsService
     */
    private $permissions;

    /**
     * @var Twig_Environment
     */
    private $twig;

    /**
     * @param Session $session
     * @param PermissionsService $permissions
     * @param Twig_Environment $twig
     */
    public function __construct(Session $session, PermissionsService $permissions, Twig_Environment $twig)
    {
        $this->session = $session;
        $this->permissions = $permissions;
        $this->twig = $twig;
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @throws Stop
     *
     * @return null
     */
    public function __invoke(Request $request, Response $response)
    {
        $account = $this->session->get('ldap-user');

        if (!($account instanceof User) || !$this->permissions->allowAdmin($account)) {
            $response->status(403);
            $response->body($this->twig->loadTemplate('denied.twig')->render([]));
            throw new Stop;
        }
    }
}
