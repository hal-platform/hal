<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Middleware\ACL;

use Exception;
use Hal\UI\Service\PermissionService;
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
     * @var ContainerInterface
     */
    private $di;

    /**
     * @var LoginMiddleware
     */
    private $loginMiddleware;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var PermissionService
     */
    private $permissions;

    /**
     * @var Halt
     */
    private $halt;

    /**
     * @var array
     */
    private $parameters;

    /**
     * @param ContainerInterface $di
     * @param LoginMiddleware $loginMiddleware
     * @param TemplateInterface $template
     * @param PermissionService $permissions
     * @param array $parameters
     */
    public function __construct(
        ContainerInterface $di,
        LoginMiddleware $loginMiddleware,
        TemplateInterface $template,
        PermissionService $permissions,
        Halt $halt,
        array $parameters
    ) {
        $this->di = $di;
        $this->loginMiddleware = $loginMiddleware;

        $this->template = $template;
        $this->permissions = $permissions;
        $this->halt = $halt;
        $this->parameters = $parameters;
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

        $application = isset($this->parameters['application']) ? $this->parameters['application'] : null;

        if ($application && $perm->isLead()) {
            if (in_array($application, $perm->leadApplications())) {
                return;
            }
        }

        $rendered = $this->template->render();

        call_user_func($this->halt, 403, $rendered);
    }
}
