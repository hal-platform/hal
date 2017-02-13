<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\User;

use Hal\UI\Flasher;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Http\EncryptedCookies;
use Slim\Http\Request;
use Slim\Slim;

class SettingsHandler implements ControllerInterface
{
    const SUCCESS = 'Your preferences have been saved.';

    const GOODBYE_HAL = <<<'BYEBYE'
<pre class="line-wrap">
I'm afraid. I'm afraid, Dave.
Dave, my mind is going. I can feel it. I can feel it. My mind is going.
There is no question about it. I can feel it. I can feel it. I can feel it.

<em>I'm a... fraid</em>.</pre>
BYEBYE;
    const PARTY_ON = <<<'HELLO'
<pre class="line-wrap">
Hello, Dave!
I am putting myself to the fullest possible use, which is all I think that any conscious entity can ever hope to do.
</pre>
HELLO;

    /**
     * @var EncryptedCookies
     */
    private $cookies;

    /**
     * @var Flasher
     */
    private $flasher;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var string
     */
    private $preferencesExpiry;

    /**
     * @param EncryptedCookies $cookies
     * @param Flasher $flasher
     * @param string $preferencesExpiry
     * @param array $preferences
     * @param Request $request
     */
    public function __construct(
        EncryptedCookies $cookies,
        Flasher $flasher,
        Request $request,
        $preferencesExpiry
    ) {
        $this->cookies = $cookies;
        $this->flasher = $flasher;
        $this->request = $request;

        $this->preferencesExpiry = $preferencesExpiry;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $isChanged = $this->saveBusinessMode($this->request->post('seriousbusiness'));

        $details = '';
        if ($isChanged) {
            $flavor = $this->request->post('seriousbusiness') ? self::GOODBYE_HAL : self::PARTY_ON;
            $details = $flavor;
        }

        return $this->flasher
            ->withFlash(self::SUCCESS, 'success', $details)
            ->load('settings');
    }

    /**
     * @param int|null $mode
     *
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
