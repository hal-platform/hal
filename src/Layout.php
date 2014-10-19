<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal;

use MCP\Corp\Account\User;
use QL\Hal\Services\PermissionsService;
use Twig_Template;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @todo This needs to be removed in favor of global context.
 *
 * This depends on the container instead of the user directly so that it can
 * lazy load the user and fail gracefully if the synthetic user was never injected.
 *
 * Previously, the user was injected directly into the class, and it was flooding core with errors
 * about the user being synthetic/uninitialized. Not sure if this affected users.
 */
class Layout
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
     * @param ContainerInterface $di
     * @param PermissionsService $permissions
     */
    public function __construct(ContainerInterface $di, PermissionsService $permissions)
    {
        $this->di = $di;
        $this->permissions = $permissions;
    }

    /**
     * @deprecated Use ::render() instead!
     *
     * @param Twig_Template $tpl
     * @param array $renderData
     * @return string
     */
    public function renderTemplateWithLayoutData(Twig_Template $tpl, array $renderData)
    {
        return $this->render($tpl, $renderData);
    }

    /**
     *  Render a template with data
     *
     *  @param Twig_Template $template
     *  @param array $data
     *  @return string
     */
    public function render(Twig_Template $template, array $data = [])
    {
        $layoutData = [
            'isAdmin' => false,
            'allowDelete' => false
        ];

        // Only load user if it has been initialized
        if ($this->di->initialized('user')) {
            $user = $this->di->get('user');
            $layoutData = [
                'isAdmin' => $this->permissions->allowAdmin($user),
                'allowDelete' => $this->permissions->allowDelete($user),
                'currentUser' => $user
            ];
        }

        $data = array_merge($data, $layoutData);

        return $template->render($data);
    }
}
