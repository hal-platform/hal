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
     *
     * @return array|null
     */
    public function flash($action = null)
    {
        $flashes = $this->getFlashes();

        if (func_num_args() > 0 && is_bool($action)) {
            if (!$action) {
                // Reset flash
                $this->set(self::FLASH_KEY, []);
            }

            return $flashes;
        }

        if (func_num_args() > 0 && is_string($action)) {
            // Add flash
            $flashes[] = $action;
            $this->set(self::FLASH_KEY, $flashes);
            return;
        }

        // Reset flash
        $this->set(self::FLASH_KEY, []);

        return $flashes;
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
