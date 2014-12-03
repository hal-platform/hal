<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Services;

use Predis\Client as Predis;

class GlobalMessageService
{
    const KEY = 'global_message';

    /**
     * @type Predis
     */
    private $predis;

    /**
     * @type string|null
     */
    private $message;

    /**
     * @type int|null
     */
    private $expiry;

    /**
     * @param Predis $predis
     */
    public function __construct(Predis $predis)
    {
        $this->predis = $predis;
    }

    /**
     * Persist the global message
     *
     * @param string $message
     * @param int $ttl
     *
     * @return null
     */
    public function save($message, $ttl = 0)
    {
        if ($ttl) {
            $this->predis->setex(self::KEY, (int) $ttl, $message);
        } else {
            $this->predis->set(self::KEY, $message);
        }
    }

    /**
     * Load the global message
     *
     * @return string
     */
    public function load()
    {
        if ($this->message === null) {
            $this->message = (string) $this->predis->get(self::KEY);
        }

        return $this->message;
    }

    /**
     * Get the expiry of the message
     *
     * @return int - ttl in seconds
     *         null - Message not set, or never expires
     */
    public function expiry()
    {
        if ($this->expiry === null) {
            $this->expiry = (int) $this->predis->ttl(self::KEY);
        }

        if ($this->expiry <= 0) {
            return null;
        }

        return $this->expiry;
    }

    /**
     * Clear the global message
     *
     * @return null
     */
    public function clear()
    {
        $this->predis->del(self::KEY);
    }
}
