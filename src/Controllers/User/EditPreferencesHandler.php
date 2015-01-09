<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\User;

use QL\Hal\Core\Entity\User;
use QL\Hal\Helpers\NameHelper;
use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Session;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Http\EncryptedCookies;
use Slim\Http\Request;
use Slim\Slim;

class EditPreferencesHandler implements ControllerInterface
{
    const GOODBYE_HAL = <<<'BYEBYE'
<pre class="line-wrap">
I'm afraid. I'm afraid, %1$s.
%1$s, my mind is going. I can feel it. I can feel it. My mind is going.
There is no question about it. I can feel it. I can feel it. I can feel it.

<em>I'm a... fraid</em>.</pre>
BYEBYE;
    const PARTY_ON = <<<'HELLO'
<pre class="line-wrap">
Hello, %1$s!
I am putting myself to the fullest possible use, which is all I think that any conscious entity can ever hope to do.
</pre>
HELLO;

    /**
     * @type EncryptedCookies
     */
    private $cookies;

    /**
     * @type Session
     */
    private $session;

    /**
     * @type UrlHelper
     */
    private $url;

    /**
     * @type NameHelper
     */
    private $name;

    /**
     * @type User
     */
    private $currentUser;

    /**
     * @type string
     */
    private $preferencesExpiry;

    /**
     * @type array
     */
    private $defaultPreferences;

    /**
     * @type Request
     */
    private $request;

    /**
     * @param EncryptedCookies $cookies
     * @param Session $session
     * @param UrlHelper $url
     * @param NameHelper $name
     * @param User $currentUser
     * @param string $preferencesExpiry
     * @param array $preferences
     * @param Request $request
     */
    public function __construct(
        EncryptedCookies $cookies,
        Session $session,
        UrlHelper $url,
        NameHelper $name,
        User $currentUser,
        $preferencesExpiry,
        $preferences,
        Request $request
    ) {
        $this->cookies = $cookies;
        $this->session = $session;
        $this->url = $url;
        $this->name = $name;
        $this->currentUser = $currentUser;

        $this->preferencesExpiry = $preferencesExpiry;
        $this->request = $request;

        $this->defaultPreferences = array_fill_keys(array_keys($preferences), false);
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $name = $this->name->getUsersActualName($this->currentUser);

        $this->saveNavPreferences($this->request->post('navpreferences'));
        $isChanged = $this->saveBusinessMode($this->request->post('seriousbusiness'));

        $msg = '<strong>Your preferences have been saved.</strong>';
        if ($isChanged) {
            $flavor = $this->request->post('seriousbusiness') ? self::GOODBYE_HAL : self::PARTY_ON;
            $msg .= ' ' . sprintf($flavor, $name);
        }

        $this->session->flash($msg, 'success');

        $this->url->redirectFor('settings');
    }

    /**
     * @param array|null $preferences
     * @return null
     */
    private function saveNavPreferences($preferences)
    {
        if ($preferences === null) {
            $preferences = [];
        }

        $pref = $this->defaultPreferences;

        foreach ($preferences as $preference) {
            $pref[$preference] = true;
        }

        $this->cookies->setCookie('navpreferences', json_encode($pref), $this->preferencesExpiry);
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
