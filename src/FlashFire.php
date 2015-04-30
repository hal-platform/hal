<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal;

use QL\Panthor\Utility\Url;

class FlashFire
{
    /**
     * @type Session
     */
    private $session;

    /**
     * @type Url
     */
    private $url;

    /**
     * @param Session $session
     * @param Url $url
     */
    public function __construct(Session $session, Url $url)
    {
        $this->session = $session;
        $this->url = $url;
    }

    /**
     * @param string $flashMessage
     * @param string $route
     * @param string $flashType
     * @param array $routeParameters
     *
     * @throws Redirect Exception
     *
     * @return void
     */
    public function fire($flashMessage, $route, $flashType = null, array $routeParameters = [])
    {
        $this->session->flash($flashMessage, $flashType);
        $this->url->redirectFor($route, $routeParameters);
    }
}
