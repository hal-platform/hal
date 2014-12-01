<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal;

use MCP\Crypto\AES;
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
     * @type EncryptedCookies
     */
    private $cookies;

    /**
     * @type AES
     */
    private $encryption;

    /**
     * @type array
     */
    private $settings;

    /**
     * Used to detect whether the cookie has changed and is worth writing out back to the user.
     *
     * @type string|null
     */
    private $cookieHash;

    /**
     * @param EncryptedCookies $cookies
     * @param AES $encryption
     * @param array $settings
     */
    public function __construct(EncryptedCookies $cookies, AES $encryption, array $settings = [])
    {
        $this->cookies = $cookies;
        $this->encryption = $encryption;
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
            $decrypted = $this->encryption->decrypt($serialized);
            if (is_string($decrypted)) {
                $unserialized = unserialize($decrypted);
                if ($unserialized instanceof Session) {
                    return $unserialized;
                }
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

        $encrypted = $this->encryption->encrypt($serialized);

        // Skip cookie rendering if it was not modified
        if ($this->cookieHash && $this->cookieHash === sha1($encrypted)) {
            return;
        }

        // If cookie size is too big, kill everything.
        if (strlen($encrypted) > 4096) {
            $serialized = serialize(new Session);
            $encrypted = $this->encryption->encrypt($serialized);
        }

        $this->cookies->setCookie(
            $this->settings['name'],
            $encrypted,
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
