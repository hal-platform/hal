<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Security;

use QL\MCP\Common\Clock;
use function random_bytes;

class CSRFManager
{
    const SECRET_BYTES = 32;
    const CSRF_LIST_SIZE = 10;
    const CSRF_VALID_AGE = '8 hours';

    /**
     * @var Clock
     */
    private $clock;

    /**
     * @var array
     */
    private $loadedCSRFs;

    /**
     * @var string
     */
    private $loadedSessionID;

    /**
     * @param Clock $clock
     */
    public function __construct(Clock $clock)
    {
        $this->clock = $clock;

        $this->loadedCSRFs = [];
        $this->loadedSessionID = '';
    }

    /**
     * @param string $form
     *
     * @return string
     */
    public function generateToken($form): string
    {
        $secret = random_bytes(self::SECRET_BYTES);

        $scope = $this->generateScope($form);
        $stored = implode('.', [$scope, bin2hex($secret)]);

        $this->saveToken($stored);

        $token = $this->encodeToken($secret);

        return $token;
    }

    /**
     * @param string $token
     * @param string $form
     *
     * @return bool
     */
    public function isTokenValid($token, $form): bool
    {
        if (!$token || !$form) {
            return false;
        }

        if (!$secret = $this->decodeToken($token)) {
            return false;
        }

        $scope = $this->generateScope($form);
        $stored = implode('.', [$scope, bin2hex($secret)]);

        $goodAfter = $this->clock
            ->read()
            ->modify('-' . self::CSRF_VALID_AGE);

        foreach ($this->loadedCSRFs as $i => $loaded) {
            list ($csrf, $created) = $loaded;
            $created = $this->clock->fromString($created);

            $isExpiryValid = ($goodAfter->compare($created) === -1);
            $isValid = hash_equals($csrf, $stored);

            if ($isValid) {
                unset($this->loadedCSRFs[$i]);
                return $isExpiryValid;
            }
        }

        return false;
    }

    /**
     * Load stored CSRFs so they can be used for validation. CSRFs must be loaded
     * at runtime in a middleware.
     *
     * The same middleware should be used to render the CSRFs back out to the cookie/etc
     *
     * @param array $csrfs
     * @param string $sessionID
     *
     * @return void
     */
    public function loadCSRFs(array $csrfs, $sessionID): void
    {
        $this->loadedCSRFs = $csrfs;
        $this->loadedSessionID = $sessionID;
    }

    /**
     * @return array
     */
    public function getCSRFs(): array
    {
        return $this->loadedCSRFs;
    }

    /**
     * @param string $token
     *
     * @return void
     */
    private function saveToken($token)
    {
        $created = $this->clock
            ->read()
            ->format('Y-m-d\TH:i:s\Z', 'UTC');

        $this->loadedCSRFs[] = [$token, $created];

        if (count($this->loadedCSRFs) > self::CSRF_LIST_SIZE) {
            array_shift($this->loadedCSRFs);
        }
    }

    /**
     * @param string $form
     *
     * @return string
     */
    private function generateScope($form)
    {
        $scope = $form;

        if ($this->loadedSessionID) {
            $scope = $this->loadedSessionID . ':' . $scope;
        }

        return $scope;
    }

    /**
     * @param string $input
     *
     * @return string
     */
    private function encodeToken($input)
    {
        $encoded = base64_encode($input);

        return rtrim(strtr($encoded, '+/', '-_'), '=');
    }

    /**
     * @param string $input
     *
     * @return string
     */
    private function decodeToken($input)
    {
        $encoded = strtr($input, '-_', '+/');
        $decoded = base64_decode($encoded, true);
        if ($decoded === false) {
            return '';
        }

        return $decoded;
    }
}
