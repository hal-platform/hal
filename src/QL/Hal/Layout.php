<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal;

use MCP\Corp\Account\User;
use Twig_Template;

class Layout
{
    /**
     * @var User
     */
    private $currentUserContext;

    /**
     * @var PushPermissionService
     */
    private $pushPermissionsService;

    /**
     * @param User $currentUserContext
     * @param PushPermissionService $pushPermissionsService
     */
    public function __construct(
        User $currentUserContext,
        PushPermissionService $pushPermissionsService
    ) {
        $this->currentUserContext = $currentUserContext;
        $this->pushPermissionsService = $pushPermissionsService;
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
        $data = array_merge(
            $data,
            [
                'commonId' => $this->currentUserContext->commonId(),
                'isAdmin'  => $this->pushPermissionsService->isUserAdmin($this->currentUserContext)
            ]
        );

        return $template->render($data);
    }
}
