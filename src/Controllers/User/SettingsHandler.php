<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\User;

use QL\Hal\Core\Entity\User;
use QL\Hal\Flasher;
use QL\Hal\Utility\NameFormatter;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Http\EncryptedCookies;
use Slim\Http\Request;
use Slim\Slim;

class SettingsHandler implements ControllerInterface
{
    const SUCCESS = 'Your preferences have been saved.';

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
     * @type Flasher
     */
    private $flasher;

    /**
     * @type NameFormatter
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
     * @type Request
     */
    private $request;

    /**
     * @param EncryptedCookies $cookies
     * @param Flasher $flasher
     * @param NameFormatter $name
     * @param User $currentUser
     * @param string $preferencesExpiry
     * @param array $preferences
     * @param Request $request
     */
    public function __construct(
        EncryptedCookies $cookies,
        Flasher $flasher,
        NameFormatter $name,
        User $currentUser,
        $preferencesExpiry,
        Request $request
    ) {
        $this->cookies = $cookies;
        $this->flasher = $flasher;
        $this->name = $name;
        $this->currentUser = $currentUser;

        $this->preferencesExpiry = $preferencesExpiry;
        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $name = $this->name->getUsersActualName($this->currentUser);

        $isChanged = $this->saveBusinessMode($this->request->post('seriousbusiness'));

        $details = '';
        if ($isChanged) {
            $flavor = $this->request->post('seriousbusiness') ? self::GOODBYE_HAL : self::PARTY_ON;
            $details = sprintf($flavor, $name);
        }

        return $this->flasher
            ->withFlash(self::SUCCESS, 'success', $details)
            ->load('settings');
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
