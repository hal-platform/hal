<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal;

use QL\Hal\Core\Entity\User;
use Slim\Helper\Set;

class Session extends Set
{
    const FLASH_KEY = 'flash';

    /**
     * @type User|null
     */
    private $user;

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
     * @param string|null $flashDetails
     *
     * @return array|Flash|null
     */
    public function flash($action = null, $type = null, $details = null)
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
     * @param User|null $user
     *
     * @return User|null
     */
    public function user(User $user = null)
    {
        if (func_num_args() === 1) {
            $this->user = $user;
        }

        return $this->user;
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
     * @param string $details
     *
     * @return Flash
     */
    private function setFlash($message, $type = null, $details = '')
    {
        $type = ($type) ? $type : Flash::INFO;
        $flash = new Flash($message, $type, $details);

        $flashes = $this->getFlashes();
        $flashes[] = $flash;
        $this->set(self::FLASH_KEY, $flashes);

        return $flash;
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
