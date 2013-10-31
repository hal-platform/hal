<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal;

/**
 *  Session Handler
 */
class Session
{
    const FLASH_KEY = 'flash';

    /**
     *  Constructor
     */
    public function __construct()
    {
        $this->start();

        if (false == $this->has(self::FLASH_KEY)) {
            $this->set(self::FLASH_KEY, array());
        }
    }

    /**
     *  Check if session is started
     *
     *  @return bool
     */
    public function started()
    {
        return (session_status() == PHP_SESSION_ACTIVE) ? true : false;
    }

    /**
     *  Start the session if it hasn't already been started
     */
    public function start()
    {
        if (false == $this->started()) {
            session_start();
        }
    }

    /**
     *  End the current session
     */
    public function end()
    {
        if ($this->started()) {
            session_destroy();
        }
    }

    /**
     *  Set a key value pair in session
     *
     *  @param string $key
     *  @param mixed $val
     */
    public function set($key, $val)
    {
        if (is_resource($val)) {
            // BAD! BAD!
        }

        $_SESSION[$key] = $val;
    }

    /**
     *  Check if the session has a key
     *
     *  @param $key
     *  @return bool
     */
    public function has($key)
    {
        return (isset($_SESSION[$key])) ? true : false;
    }

    /**
     *  Get a value from session, returned default if not set
     *
     *  @param string $key
     *  @param mixed $default
     *  @return mixed
     */
    public function get($key, $default = null)
    {
        return ($this->has($key)) ? $_SESSION[$key] : $default;
    }

    /**
     *  Check if any flash messages are available
     *
     * @param null $key
     * @return bool
     */
    public function hasFlash($key = null)
    {
        $messages = $this->get(self::FLASH_KEY);

        if ($key === null) {
            return (count($messages) > 0) ? true : false;
        } else {
            return (isset($messages[$key])) ? true : false;
        }

    }

    /**
     *  Get an array of flash messages
     *
     *  @param null $key
     *  @return array
     */
    public function getFlash($key = null)
    {
        $messages = array();

        if ($this->hasFlash($key)) {
            if ($key === null) {
                $messages = $this->get(self::FLASH_KEY);
                $this->set(self::FLASH_KEY, array());
            } else {
                $messages = $this->get(self::FLASH_KEY);
                $message = $messages[$key];
                unset($messages[$key]);
                $this->set(self::FLASH_KEY, $messages);
                $messages = array($message);
            }
        }

        return $messages;
    }

    /**
     *  Add a new flash message
     *
     *  @param $message
     *  @param $key
     */
    public function addFlash($message, $key = null)
    {
        $messages = $this->get(self::FLASH_KEY);

        if ($key === null) {
            $messages[] = $message;
        } else {
            $messages[$key] = $message;
        }

        $this->set(self::FLASH_KEY, $messages);
    }
}
