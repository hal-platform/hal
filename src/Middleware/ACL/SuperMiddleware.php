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

class SuperMiddleware implements MiddlewareInterface
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
     * @inheritDoc
     * @throws Exception
     */
    public function __invoke()
    {
        // Ensure user is logged in first
        call_user_func($this->loginMiddleware);

        $user = $this->di->get('currentUser');
        $perm = $this->permissions->getUserPermissions($user);

        if ($perm->isSuper()) {
            return;
        }

        $rendered = $this->template->render();

        call_user_func($this->halt, 403, $rendered);
    }
}
