<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Bouncers;

use QL\Hal\Services\PermissionsService;
use QL\Hal\Session;
use QL\Panthor\TemplateInterface;
use Slim\Exception\Stop;
use Slim\Http\Request;
use Slim\Http\Response;

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
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var LoginBouncer
     */
    private $loginBouncer;

    /**
     * @param Session $session
     * @param PermissionsService $permissions
     * @param TemplateInterface $template
     * @param LoginBouncer $loginBouncer
     */
    public function __construct(Session $session, PermissionsService $permissions, TemplateInterface $template, LoginBouncer $loginBouncer)
    {
        $this->session = $session;
        $this->permissions = $permissions;
        $this->template = $template;
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

        $rendered = $this->template->render();
        $response->setStatus(403);
        $response->setBody($rendered);

        throw new Stop;
    }
}
