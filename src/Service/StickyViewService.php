<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Service;

use QL\Panthor\Http\EncryptedCookies;
use QL\Panthor\Utility\Json;

class StickyViewService
{
    const COOKIE_NAME = 'stickyview';

    /**
     * @type EncryptedCookies
     */
    private $cookies;

    /**
     * @type EncryptedCookies
     */
    private $json;

    /**
     * @type string
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
