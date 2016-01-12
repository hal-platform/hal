<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal;

use QL\Panthor\Utility\Url;

class Flasher
{
    /**
     * @var Session
     */
    private $session;

    /**
     * @var Url
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
     * @param string $route
     * @param string $parameters
     *
     * @throws Redirect Exception
     *
     * @return void
     */
    public function load($route, $parameters = [])
    {
        $this->url->redirectFor($route, $parameters);
    }

    /**
     * @param string $message
     * @param string $type
     * @param string $details
     *
     * @return self
     */
    public function withFlash($message, $type = null, $details = null)
    {
        call_user_func_array([$this->session, 'flash'], func_get_args());

        return $this;
    }
}
