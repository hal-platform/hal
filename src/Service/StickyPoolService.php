<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Service;

use QL\Panthor\Http\EncryptedCookies;
use QL\Panthor\Utility\Json;

class StickyPoolService
{
    const COOKIE_NAME = 'stickypool';

    /**
     * @var EncryptedCookies
     */
    private $cookies;

    /**
     * @var EncryptedCookies
     */
    private $json;

    /**
     * @var string
     */
    private $preferencesExpiry;

    /**
     * @param EncryptedCookies $cookies
     * @param Json $json
     * @param array $preferencesExpiry
     */
    public function __construct(EncryptedCookies $cookies, Json $json, $preferencesExpiry)
    {
        $this->cookies = $cookies;
        $this->json = $json;
        $this->preferencesExpiry = $preferencesExpiry;
    }

    /**
     * @param string $applicationID
     * @param string $environmentID
     * @param string|null $viewID
     *
     * @return void
     */
    public function save($applicationID, $environmentID, $viewID)
    {
        // we store each app stickyness individually per app, but in the same cookie.
        $stickies = $this->unpackStickies();

        if ($viewID == null) {
            unset($stickies[$applicationID][$environmentID]);
        } else {
            $stickies[$applicationID][$environmentID] = $viewID;
        }

        $this->cookies->setCookie(self::COOKIE_NAME, $this->json->encode($stickies), $this->preferencesExpiry);
    }

    /**
     * Get the current view preference for an application.
     *
     * @param string $applicationID
     * @param string $environmentID
     *
     * @return string|null
     */
    public function get($applicationID, $environmentID)
    {
        $stickies = $this->unpackStickies();
        if (isset($stickies[$applicationID][$environmentID])) {
            return $stickies[$applicationID][$environmentID];
        }

        return null;
    }

    /**
     * @return array
     */
    private function unpackStickies()
    {
        $stickies = $this->cookies->getCookie(self::COOKIE_NAME);

        // if the cookie is set
        if ($stickies !== null) {
            $stickies = $this->json->decode($stickies);
        }

        // decoded invalid, or not set
        if (!is_array($stickies)) {
            $stickies = [];
        }

        return $stickies;
    }
}
