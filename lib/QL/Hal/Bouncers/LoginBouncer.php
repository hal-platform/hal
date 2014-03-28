<?php
# lib/QL/Hal/Bouncers/LoginBouncer.php

namespace QL\Hal\Bouncers;

use Slim\Http\Request;
use Slim\Http\Response;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use QL\Hal\Session;

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
     *  @param Session $session
     *  @param ContainerBuilder $container
     */
    public function __construct(Session $session, ContainerBuilder $container)
    {
        $this->session = $session;
        $this->container = $container;
    }

    /**
     *  @param Request $request
     *  @param Response $response
     */
    public function __invoke(Request $request, Response $response)
    {
        if (!$this->session->get('account')) {
            $response->redirect($request->getScheme() . '://' . $request->getHostWithPort() . '/', 302);
        }
        $this->container->set('currentUserContext', $this->session->get('account'));
    }
}
