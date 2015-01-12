<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers;

use QL\Hal\Session;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Utility\Url;

class LogoutController implements ControllerInterface
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
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $this->session->clear();
        $this->url->redirectFor('login');
    }
}
