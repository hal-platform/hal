<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
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
