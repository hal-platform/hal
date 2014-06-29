<?php

namespace QL\Hal\Controllers;

use QL\Hal\Services\PermissionsService;
use Twig_Template;
use Slim\Http\Request;
use Slim\Http\Response;
use QL\Hal\Layout;
use MCP\Corp\Account\User as LdapUser;

/**
 *  Hello Controller
 *
 *  @author Matt Colf <matthewcolf@quickenloans.com>
 */
class HelloController
{
    /**
     *  @var Twig_Template
     */
    private $template;

    /**
     *  @var Layout
     */
    private $layout;

    /**
     *  @var LdapUser
     */
    private $user;

    /**
     * @var PermissionsService
     */
    private $permissions;

    /**
     * @param Twig_Template $template
     * @param Layout $layout
     * @param LdapUser $user
     * @param PermissionsService $permissions
     */
    public function __construct(
        Twig_Template $template,
        Layout $layout,
        LdapUser $user,
        PermissionsService $permissions
    ) {
        $this->template = $template;
        $this->layout = $layout;
        $this->user = $user;
        $this->permissions = $permissions;
    }

    /**
     *  Run the controller
     *
     *  @param Request $request
     *  @param Response $response
     *  @param array $params
     */
    public function __invoke(Request $request, Response $response, array $params = [])
    {

        $response->body(
            $this->layout->render(
                $this->template,
                [
                    'user' => $this->user,
                    'repos' => $this->permissions->userRepositories($this->user)
                ]
            )
        );
    }
}
