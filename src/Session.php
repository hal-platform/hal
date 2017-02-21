<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI;

use JsonSerializable;
use QL\Hal\Core\Entity\User;
use const JSON_UNESCAPED_SLASHES;
use const JSON_PRESERVE_ZERO_FRACTION;

class Session implements SessionInterface
{
    /**
     * @var array
     */
    private $data;
    private $original;

    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $this->original = $data;
    }

    /**
     * @param string $data
     *
     * @return SessionInterface|null
     */
    public static function fromCookie($data)
    {
        if (!$data) {
            return null;
        }

        $decoded = json_decode($data, true);

        if (!is_array($decoded)) {
            return null;
        }

        return new self($decoded);
    }

    /**
     * @param SessionInterface $session
     *
     * @return string
     */
    public static function toCookie(SessionInterface $session)
    {
        return json_encode($session, JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, $value)
    {
        $this->data[$key] = self::convertValueToScalar($value);
    }

    /**
     * @inheritDoc
     */
    public function get(string $key, $default = null)
    {
        if (!$this->has($key)) {
            return self::convertValueToScalar($default);
        }

        return $this->data[$key];
    }

    /**
     * @inheritDoc
     */
    public function remove(string $key)
    {
        unset($this->data[$key]);
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
        $this->data = [];
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * @inheritDoc
     */
    public function hasChanged() : bool
    {
        return $this->data !== $this->original;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return $this->data;
    }

    /**
     * @param int|bool|string|float|array|object|JsonSerializable $value
     *
     * @return int|bool|string|float|array
     */
    private static function convertValueToScalar($value)
    {
        return json_decode(json_encode($value, JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION), true);
    }
}
