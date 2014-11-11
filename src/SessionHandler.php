<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal;

use QL\Panthor\Http\EncryptedCookies;

class SessionHandler
{
    /**
     * @type array
     */
    protected static $defaultSettings = [
        'name' => 'session',
        'lifetime' => '8 hours',
        'path' => '/',
        'domain' => null,
        'secure' => false,
        'httponly' => false
    ];

    /**
     * @type array
     */
    private $settings;

    /**
     * @type EncryptedCookies
     */
    private $cookies;

    /**
     * Used to detect whether the cookie has changed and is worth writing out back to the user.
     *
     * @type string|null
     */
    private $cookieHash;

    /**
     * @param EncryptedCookies $cookies
     * @param array $settings
     */
    public function __construct(EncryptedCookies $cookies, array $settings = [])
    {
        $this->cookies = $cookies;
        $this->settings = array_merge(static::$defaultSettings, $settings);
    }

    /**
     * @return Session
     */
    public function load()
    {
        $serialized = $this->cookies->getCookie($this->settings['name']);
        $this->cookieHash = sha1($serialized);

        if ($serialized) {
            $unserialized = unserialize($serialized);
            if ($unserialized instanceof Session) {
                return $unserialized;
            }
        }

        // If: No cookie present
        // If: Serialized data is invalid
        // If: Serialized data is not Session
        return new Session;
    }

    /**
     * @param Session $session
     * @return null
     */
    public function save(Session $session)
    {
        $serialized = serialize($session);

        // Skip cookie rendering if it was not modified
        if ($this->cookieHash && $this->cookieHash === sha1($serialized)) {
            return;
        }

        // If cookie size is too big, kill everything.
        if (strlen($serialized) > 4096) {
            $serialized = serialize(new Session);
        }

        $this->cookies->setCookie(
            $this->settings['name'],
            $serialized,
            $this->settings['lifetime'],
            $this->settings['path'],
            $this->settings['domain'],
            $this->settings['secure'],
            $this->settings['httponly']
        );
    }

    /**
     * @return boolean
     */
    public function isLoaded()
    {
        return ($this->cookieHash !== null);
    }
}
