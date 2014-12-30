<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Middleware\Bouncer;

use QL\Hal\Services\PermissionsService;
use QL\Hal\Session;
use Slim\Exception\Stop;
use Slim\Http\Request;
use Slim\Http\Response;
use QL\Panthor\TemplateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A bouncer that checks to see if the current user is a super admin
 */
class SuperAdminBouncer
{
    /**
     * @var ContainerInterface
     */
    private $di;

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
     * @param ContainerInterface $di
     * @param PermissionsService $permissions
     * @param TemplateInterface $twig
     * @param LoginBouncer $loginBouncer
     */
    public function __construct(
        ContainerInterface $di,
        PermissionsService $permissions,
        TemplateInterface $twig,
        LoginBouncer $loginBouncer
    ) {
        $this->di = $di;
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

        $user = $this->di->get('currentUser');

        if ($this->permissions->allowSuperAdmin($user)) {
            return;
        }

        $rendered = $this->twig->render([]);
        $response->setStatus(403);
        $response->setBody($rendered);

        throw new Stop;
    }
}
