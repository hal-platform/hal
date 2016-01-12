<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Service;

use QL\Hal\Core\Entity\Application;
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
     * @param Application|string $applicationID
     * @param string $environmentID
     *
     * @return void
     */
    public function save($applicationID, $environmentID)
    {
        if ($applicationID instanceof Application) {
            $applicationID = $applicationID->id();
        }

        // we store each repo stickyness individually per repo, but in the same cookie.
        $stickies = $this->unpackStickies();
        $stickies[$applicationID] = $environmentID;

        $this->cookies->setCookie(self::COOKIE_NAME, $this->json->encode($stickies), $this->preferencesExpiry);
    }

    /**
     * Get the current env preference for an application.
     *
     * @param Application|string $applicationID
     *
     * @return string|null
     */
    public function get($applicationID)
    {
        if ($applicationID instanceof Application) {
            $applicationID = $applicationID->id();
        }

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
