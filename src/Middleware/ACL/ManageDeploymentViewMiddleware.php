<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Middleware\ACL;

use Exception;
use Hal\UI\Service\PermissionService;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\DeploymentView;
use QL\Hal\Core\Entity\User;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Slim\Halt;

/**
 * Note: Admins and Supers also pass this middleware bouncer.
 */
class ManageDeploymentViewMiddleware implements MiddlewareInterface
{
    /**
     * @var PermissionService
     */
    private $permissions;

    /**
     * @var Halt
     */
    private $halt;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var User
     */
    private $currentUser;

    /**
     * @var DeploymentView
     */
    private $view;

    /**
     * @param PermissionService $permissions
     * @param Halt $halt
     * @param TemplateInterface $template
     * @param User $currentUser
     * @param DeploymentView $view
     */
    public function __construct(
        PermissionService $permissions,
        Halt $halt,
        TemplateInterface $template,
        User $currentUser,
        DeploymentView $view
    ) {
        $this->permissions = $permissions;
        $this->halt = $halt;
        $this->template = $template;

        $this->currentUser = $currentUser;
        $this->view = $view;
    }

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function __invoke()
    {
        // owner
        if ($this->view->user() === $this->currentUser) {
            return;
        }

        // admin, lead
        if ($this->canUserManage($this->currentUser, $this->view->application())) {
            return;
        }

        // has no user = shared, check build permissions
        if (!$this->view->user()) {
            if ($this->canPeerManage($this->currentUser, $this->view->application())) {
                return;
            }
        }

        $rendered = $this->template->render();

        call_user_func($this->halt, 403, $rendered);
    }

    /**
     * @param User $user
     * @param Application|null $application
     *
     * @return bool
     */
    private function canUserManage(User $user, Application $application = null)
    {
        $perm = $this->permissions->getUserPermissions($user);

        // admin
        if ($perm->isButtonPusher() || $perm->isSuper()) {
            return true;
        }

        if (!$application) {
            return false;
        }

        // lead
        if ($perm->isLeadOfApplication($application)) {
            return true;
        }

        return false;
    }

    /**
     * @param User $user
     * @param Application|null $application
     *
     * @return bool
     */
    private function canPeerManage(User $user, Application $application = null)
    {
        if (!$application) {
            return false;
        }

        if ($this->permissions->canUserBuild($user, $application)) {
            return true;
        }

        return false;
    }
}
