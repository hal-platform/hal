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
     * @param string $repoId
     * @param string $environmentId
     *
     * @return void
     */
    public function save($repoId, $environmentId)
    {
        // we store each repo stickyness individually per repo, but in the same cookie.
        $stickies = $this->unpackStickies();
        $stickies[$repoId] = $environmentId;

        $this->cookies->setCookie('stickyenvironment', $this->json->encode($stickies), $this->preferencesExpiry);
    }

    /**
     * Get the current env preference for an application.
     *
     * @param string $repoId
     *
     * @return string|null
     */
    public function get($repoId)
    {
        $stickies = $this->unpackStickies();
        if (isset($stickies[$repoId])) {
            return $stickies[$repoId];
        }

        return null;
    }

    /**
     * @return array
     */
    private function unpackStickies()
    {
        $stickies = $this->cookies->getCookie('stickyenvironment');

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
