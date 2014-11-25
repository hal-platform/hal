<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal;

use Slim\Helper\Set;

class Session extends Set
{
    const FLASH_KEY = 'flash';

    /**
     * Get an array of flash messages (and clear all flashes stored)
     *
     * The provided parameter acts as either a message to store, or whether to $keep existing messages.
     *
     * Examples:
     * If a string message is provided, a flash is added.
     * If true is provided, the flash messages are retrieved and NOT cleared.
     * If false is provided, the flash messages are retrieved and cleared.
     *
     * Default behavior:
     * Retrieve flashes and clear
     *
     * @param boolean|string|null $message|$keepFlashes
     * @param string|null $flashType
     *
     * @return array|Flash|null
     */
    public function flash($action = null, $type = null)
    {
        if (func_num_args() === 1 && is_bool($action)) {
            return call_user_func_array([$this, 'getAndFlush'], func_get_args());
        }

        if (func_num_args() > 0 && is_string($action)) {
            return call_user_func_array([$this, 'setFlash'], func_get_args());
        }

        return call_user_func([$this, 'getAndFlush'], false);
    }

    /**
     * @param bool $keepFlashes
     *
     * @return Flash[]
     */
    private function getAndFlush($keepFlashes)
    {
        $flashes = $this->getFlashes();

        if (!$keepFlashes) {
            $this->set(self::FLASH_KEY, []);
        }

        return $flashes;
    }

    /**
     * @param string $message
     * @param string|null $type
     *
     * @return Flash
     */
    private function setFlash($message, $type = null)
    {
        $type = ($type) ? $type : Flash::INFO;
        $flash = new Flash($message, $type);

        $flashes = $this->getFlashes();
        $flashes[] = $flash;
        $this->set(self::FLASH_KEY, $flashes);

        return $flash;
    }

    /**
     * @deprecated Use flash($message) instead
     *
     * Add a new flash message
     *
     * @param string $message
     */
    public function addFlash($message, $key = null)
    {
        $this->flash($message);
    }

    /**
     * Flash helper
     *
     * @return array
     */
    private function getFlashes()
    {
        $messages = $this->get(self::FLASH_KEY);
        if (!is_array($messages)) {
            $messages = [];
        }

        return $messages;
    }
}
