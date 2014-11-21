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
use Twig_Template;

/**
 * A bouncer that checks to see if the current user is a super admin
 */
class SuperAdminBouncer
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
     * @var Twig_Template
     */
    private $twig;

    /**
     * @var LoginBouncer
     */
    private $loginBouncer;

    /**
     * @param Session $session
     * @param PermissionsService $permissions
     * @param Twig_Template $twig
     * @param LoginBouncer $loginBouncer
     */
    public function __construct(Session $session, PermissionsService $permissions, Twig_Template $twig, LoginBouncer $loginBouncer)
    {
        $this->session = $session;
        $this->permissions = $permissions;
        $this->twig = $twig;
        $this->loginBouncer = $loginBouncer;
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
        // Let login bouncer run first
        call_user_func($this->loginBouncer, $request, $response);

        $user = $this->session->get('user');
        if ($this->permissions->allowAdmin($user)) {
            return;
        }

        $rendered = $this->twig->render([]);
        $response->setStatus(403);
        $response->setBody($rendered);

        throw new Stop;
    }
}
