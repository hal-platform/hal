<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal;

/**
 * @api
 */
class Session
{
    public function __construct()
    {
        session_start();
    }

    /**
     * @param string $key
     * @param mixed $val
     */
    public function set($key, $val)
    {
        if (is_resource($val)) {
            // BAD! BAD!
        }

        $_SESSION[$key] = $val;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        $val = isset($_SESSION[$key]) ? $_SESSION[$key] : null;
        return $val;
    }
}
