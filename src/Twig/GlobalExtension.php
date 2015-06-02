<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Twig;

use QL\Hal\Session;
use Slim\Http\Request;
use Twig_Extension;

/**
 * Twig Extension for declaring and preparing global variables
 */
class GlobalExtension extends Twig_Extension
{
    const NAME = 'hal_global';

    /**
     * @type array
     */
    private $globals;

    /**
     * @type Request
     */
    private $request;

    /**
     * @type Session
     */
    private $session;

    /**
     * @type callable
     */
    private $lazyUser;

    /**
     * @param Request $request
     * @param Session $session
     * @param callable $lazyUser
     */
    public function __construct(
        Request $request,
        Session $session,
        callable $lazyUser
    ) {
        $this->request = $request;
        $this->session = $session;
        $this->lazyUser = $lazyUser;

        $this->globals = [];
    }

    /**
     *  Get the extension name
     *
     *  @return string
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getGlobals()
    {
        $this->addGlobal('currentUser', call_user_func($this->lazyUser));
        $this->addGlobal('isFirstLogin', $this->session->get('is-first-login'));
        $this->addGlobal('ishttpsOn', ($this->request->getScheme() === 'https'));

        return $this->globals;
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return null
     */
    public function addGlobal($key, $value)
    {
        $this->globals[$key] = $value;
    }
}
