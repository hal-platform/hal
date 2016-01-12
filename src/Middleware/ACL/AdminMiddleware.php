<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Middleware\ACL;

use Exception;
use QL\Hal\Service\PermissionService;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Slim\Halt;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Note: Supers also pass this middleware bouncer.
 */
class AdminMiddleware implements MiddlewareInterface
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
     * @type PermissionService
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
     * @param PermissionService $permissions
     */
    public function __construct(
        ContainerInterface $di,
        LoginMiddleware $loginMiddleware,
        TemplateInterface $template,
        PermissionService $permissions,
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

        $rendered = $this->template->render();

        call_user_func($this->halt, 403, $rendered);
    }
}
