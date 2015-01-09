<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Middleware\Bouncer;

use QL\Hal\Services\PermissionsService;
use QL\Hal\Session;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\TemplateInterface;
use Slim\Exception\Stop;
use Slim\Http\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A bouncer that checks to see if the current user is an admin
 */
class AdminBouncer implements MiddlewareInterface
{
    /**
     * @type ContainerInterface
     */
    private $di;

    /**
     * @type PermissionsService
     */
    private $permissions;

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type LoginBouncer
     */
    private $loginBouncer;

    /**
     * @type Response
     */
    private $response;

    /**
     * @param ContainerInterface $di
     * @param PermissionsService $permissions
     * @param TemplateInterface $template
     * @param LoginBouncer $loginBouncer
     * @param Response $response
     */
    public function __construct(
        ContainerInterface $di,
        PermissionsService $permissions,
        TemplateInterface $template,
        LoginBouncer $loginBouncer,
        Response $response
    ) {
        $this->di = $di;
        $this->permissions = $permissions;
        $this->template = $template;
        $this->loginBouncer = $loginBouncer;
        $this->response = $response;
    }

    /**
     * {@inheritdoc}
     * @throws Stop
     */
    public function __invoke()
    {
        // Let login bouncer run first
        call_user_func($this->loginBouncer);

        $user = $this->di->get('currentUser');

        if ($this->permissions->allowAdmin($user)) {
            return;
        }

        $rendered = $this->template->render();
        $this->response->setStatus(403);
        $this->response->setBody($rendered);

        throw new Stop;
    }
}
