<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\User;

use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Session;
use QL\Panthor\Http\EncryptedCookies;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Slim;
use Twig_Template;

class EditPreferencesHandler
{
    const GOODBYE_HAL = <<<BYEBYE
<br>
<pre>I'm afraid. I'm afraid, Dave.
Dave, my mind is going. I can feel it. I can feel it. My mind is going.
There is no question about it. I can feel it. I can feel it. I can feel it.

<em>I'm a... fraid</em>.</pre>
BYEBYE;
    const PARTY_ON = <<<HELLO
<br>
<pre>Hello, Dave! I am putting myself to the fullest possible use, which is all I think that any conscious entity can ever hope to do.</pre>
HELLO;

    /**
     * @var EncryptedCookies
     */
    private $cookies;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var UrlHelper
     */
    private $url;

    /**
     * @var string
     */
    private $preferencesExpiry;

    /**
     *  @param EncryptedCookies $cookies
     *  @param Session $session
     *  @param UrlHelper $url
     *  @param string $preferencesExpiry
     */
    public function __construct(EncryptedCookies $cookies, Session $session, UrlHelper $url, $preferencesExpiry)
    {
        $this->cookies = $cookies;
        $this->session = $session;
        $this->url = $url;
        $this->preferencesExpiry = $preferencesExpiry;
    }

    /**
     *  @param Request $request
     *  @param Response $response
     *  @param array $params
     *  @param callable $notFound
     */
    public function __invoke(Request $request, Response $response)
    {
        $this->saveNavPreferences($request->post('navpreferences'));
        $isChanged = $this->saveBusinessMode($request->post('seriousbusiness'));

        $msg = '<strong>Your preferences have been saved.</strong>';
        if ($isChanged) {
            $msg .= $request->post('seriousbusiness') ? ' ' . self::GOODBYE_HAL : ' ' . self::PARTY_ON;
        }

        $this->session->flash($msg);

        $this->url->redirectFor('settings');
    }

    /**
     * @param string|array $nav
     * @return null
     */
    private function saveNavPreferences($nav)
    {
        if (is_array($nav)) {
            $nav = implode(' ', $nav);
        }

        $this->cookies->setCookie('navpreferences', trim($nav), $this->preferencesExpiry);
    }

    /**
     * @param int|null $mode
     * @return bool Has the setting been updated?
     */
    private function saveBusinessMode($mode)
    {
        $seriousbusiness = (bool) $mode;

        $current = (bool) $this->cookies->getCookie('seriousbusiness');

        $this->cookies->setCookie('seriousbusiness', $seriousbusiness, $this->preferencesExpiry);

        if ($current !== $seriousbusiness) {
            return true;
        }

        return false;
    }
}
