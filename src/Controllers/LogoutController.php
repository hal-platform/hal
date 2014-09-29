<?php

namespace QL\Hal\Controllers;

use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Session;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 *  Logout Controller
 *
 *  @author Matt Colf <matthewcolf@quickenloans.com>
 */
class LogoutController
{
    /**
     *  @var Session
     */
    private $session;

    /**
     *  @var UrlHelper
     */
    private $url;

    /**
     *  @param Session $session
     *  @param UrlHelper $url
     */
    public function __construct(
        Session $session,
        UrlHelper $url
    ) {
        $this->session = $session;
        $this->url = $url;
    }

    /**
     *  @param Request $request
     *  @param Response $response
     */
    public function __invoke(Request $request, Response $response)
    {
        $this->session->end();
        $this->url->redirectFor('login');
    }
}
