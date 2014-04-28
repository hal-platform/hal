<?php
# lib/QL/Hal/Bouncers/LoginBouncer.php

namespace QL\Hal\Bouncers;

use Slim\Http\Request;
use Slim\Http\Response;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use QL\Hal\Session;
use QL\Hal\Helpers\UrlHelper;

/**
 *  A bouncer that checks to see if the current user is logged in
 *
 *  @author Matt Colf <matthewcolf@quickenloans.com>
 *  @author Matt Nagi <mattnagi@quickenloans.com>
 */
class LoginBouncer
{
    /**
     *  @var Session
     */
    private $session;

    /**
     *  @var ContainerBuilder
     */
    private $container;

    /**
     *  @var UrlHelper
     */
    private $url;

    /**
     *  @param Session $session
     *  @param ContainerBuilder $container
     *  @param UrlHelper $url
     */
    public function __construct(Session $session, ContainerBuilder $container, UrlHelper $url)
    {
        $this->session = $session;
        $this->container = $container;
        $this->url = $url;
    }

    /**
     *  @param Request $request
     *  @param Response $response
     */
    public function __invoke(Request $request, Response $response)
    {
        if (!$this->session->get('account')) {
            $this->url->redirectFor('login');
        }

        $this->container->set('user', $this->session->get('account'));
    }
}
