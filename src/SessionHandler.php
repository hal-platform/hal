<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal;

use QL\Panthor\Encryption\LibsodiumSymmetricCrypto;
use QL\Panthor\Exception\CryptoException;
use QL\Panthor\Http\EncryptedCookies;

class SessionHandler
{
    /**
     * @var array
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
     * @var EncryptedCookies
     */
    private $cookies;

    /**
     * @var LibsodiumSymmetricCrypto
     */
    private $encryption;

    /**
     * @var array
     */
    private $settings;

    /**
     * Used to detect whether the cookie has changed and is worth writing out back to the user.
     *
     * @var string|null
     */
    private $cookieHash;

    /**
     * @param EncryptedCookies $cookies
     * @param LibsodiumSymmetricCrypto $encryption
     * @param array $settings
     */
    public function __construct(EncryptedCookies $cookies, LibsodiumSymmetricCrypto $encryption, array $settings = [])
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
            try {
                $decrypted = $this->encryption->decrypt($serialized);
            } catch (CryptoException $ex) {
                $decrypted = null;
            }

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
        return $this->buildSession();
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
            $serialized = serialize($this->buildSession());
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

    /**
     * @param array|null $data
     *
     * @return Session
     */
    private function buildSession(array $data = [])
    {
        return new Session($data);
    }
}
