<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\User;

use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Flash;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\HTTP\CookieHandler;
use QL\Panthor\Utility\URI;

class SettingsMiddleware implements MiddlewareInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;

    private const MSG_SUCCESS = 'Your preferences have been saved.';

    private const SRS_COOKIE_NAME = 'seriousbusiness';

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
     * @var CookieHandler
     */
    private $cookies;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @var string
     */
    private $preferencesExpiry;

    /**
     * @param CookieHandler $cookies
     * @param URI $uri
     * @param string $preferencesExpiry
     */
    public function __construct(CookieHandler $cookies, URI $uri, $preferencesExpiry)
    {
        $this->cookies = $cookies;
        $this->uri = $uri;

        $this->preferencesExpiry = $preferencesExpiry;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if ($request->getMethod() !== 'POST') {
            return $next($request, $response);
        }

        [$current, $new] = $this->getSettings($request);

        $isChanged = ($new !== $current);

        $details = '';
        if ($isChanged) {
            $response = $this->cookies->withCookie($response, self::SRS_COOKIE_NAME, $new, $this->preferencesExpiry);
            $details = $new ? self::GOODBYE_HAL : self::PARTY_ON;
        }

        $this->withFlash($request, Flash::SUCCESS, self::MSG_SUCCESS, $details);
        return $this->withRedirectRoute($response, $this->uri, 'settings');
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return array
     */
    private function getSettings(ServerRequestInterface $request)
    {
        $current = $this->cookies->getCookie($request, self::SRS_COOKIE_NAME);
        $new = $request->getParsedBody()[self::SRS_COOKIE_NAME] ?? '';

        return [$current ? '1' : '0', $new ? '1' : '0'];
    }
}
