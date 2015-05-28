<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Middleware\ACL;

use Exception;
use QL\Hal\Service\NewPermissionsService;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Slim\Halt;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Note: Admins and Supers also pass this middleware bouncer.
 */
class LeadMiddleware implements MiddlewareInterface
{
    /**
     * @type ContainerInterface
     */
    private $di;

    /**
     * @type LoginMiddleware
     */
    private $loginMiddleware;

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type NewPermissionsService
     */
    private $permissions;

    /**
     * @type Halt
     */
    private $halt;

    /**
     * @param ContainerInterface $di
     * @param LoginMiddleware $loginMiddleware
     * @param TemplateInterface $template
     * @param NewPermissionsService $permissions
     */
    public function __construct(
        ContainerInterface $di,
        LoginMiddleware $loginMiddleware,
        TemplateInterface $template,
        NewPermissionsService $permissions,
        Halt $halt
    ) {
        $this->di = $di;
        $this->loginMiddleware = $loginMiddleware;

        $this->template = $template;
        $this->permissions = $permissions;
        $this->halt = $halt;
    }

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function __invoke()
    {
        // Ensure user is logged in first
        call_user_func($this->loginMiddleware);

        $user = $this->di->get('currentUser');
        $perm = $this->permissions->getUserPermissions($user);

        if ($perm->isButtonPusher() || $perm->isSuper()) {
            return;
        }

        // ASSUMPTION: the repository id will always be named 'repository' in the route
        // dumb, but we need to look up the repo key here for user permission checks
        $application = isset($this->parameters['repository']) ? $this->parameters['repository'] : null;

        if ($application && $perm->isLead()) {
            if (in_array($application, $perm->applications(), true)) {
                return;
            }
        }

        $rendered = $this->template->render();

        call_user_func($this->halt, 403, $rendered);
    }
}
