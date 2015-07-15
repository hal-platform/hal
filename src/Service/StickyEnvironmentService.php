<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Service;

use QL\Panthor\Http\EncryptedCookies;
use QL\Panthor\Utility\Json;

class StickyEnvironmentService
{
    const COOKIE_NAME = 'stickyenvironment';

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
     *
     * @return void
     */
    public function save($applicationID, $environmentID)
    {
        // we store each repo stickyness individually per repo, but in the same cookie.
        $stickies = $this->unpackStickies();
        $stickies[$applicationID] = $environmentID;

        $this->cookies->setCookie(self::COOKIE_NAME, $this->json->encode($stickies), $this->preferencesExpiry);
    }

    /**
     * Get the current env preference for an application.
     *
     * @param string $applicationID
     *
     * @return string|null
     */
    public function get($applicationID)
    {
        $stickies = $this->unpackStickies();
        if (isset($stickies[$applicationID])) {
            return $stickies[$applicationID];
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
