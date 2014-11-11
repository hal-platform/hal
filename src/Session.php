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
     * If a message is provided, a flash is instead added.
     *
     * @param string|null $message
     *
     * @return array|null
     */
    public function flash($message = null)
    {
        $flashes = $this->getFlashes();

        if (func_num_args() > 0) {
            // Add flash
            $flashes[] = $message;
            $this->set(self::FLASH_KEY, $flashes);
            return;
        }

        // Get flash
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
