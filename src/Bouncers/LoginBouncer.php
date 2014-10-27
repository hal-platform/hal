<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Bouncers;

use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Session;
use Slim\Exception\Stop;
use Slim\Http\Request;
use Slim\Http\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A bouncer that checks to see if the current user is logged in
 */
class LoginBouncer
{
    /**
     * @var Session
     */
    private $session;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var UrlHelper
     */
    private $url;

    /**
     * @param Session $session
     * @param ContainerInterface $container
     * @param UrlHelper $url
     */
    public function __construct(Session $session, ContainerInterface $container, UrlHelper $url)
    {
        $this->session = $session;
        $this->container = $container;
        $this->url = $url;
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @throws Stop
     *
     * @return null
     */
    public function __invoke(Request $request, Response $response)
    {
        if (!$this->session->get('account')) {
            $this->url->redirectFor('login', [], ['redirect' => $request->getPathInfo()]);
            throw new Stop;
        }

        $this->container->set('user', $this->session->get('account'));
    }
}
