<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI;

use JsonSerializable;

interface SessionInterface extends JsonSerializable
{
    /**
     * Stores a given value in the session.
     *
     * @param string $key
     * @param int|bool|string|float|array $value
     *
     * @return void
     */
    public function set(string $key, $value);

    /**
     * Retrieves a value from the session.
     *
     * @param string $key
     * @param int|bool|string|float|array $default
     *
     * @return int|bool|string|float|array
     */
    public function get(string $key, $default = null);

    /**
     * Removes an item from the session.
     *
     * @param string $key
     *
     * @return void
     */
    public function remove(string $key);

    /**
     * Clears the entire session.
     *
     * @return void
     */
    public function clear();

    /**
     * Checks whether a given key exists in the session.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Checks whether the session has changed its contents.
     *
     * @return bool
     */
    public function hasChanged(): bool;
}
