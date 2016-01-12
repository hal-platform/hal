<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Service;

use Predis\Client as Predis;

class GlobalMessageService
{
    const KEY = 'global-message';
    const TICK_KEY = 'global-message-tick';

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

    /**
     * @return bool
     */
    public function isUpdateTickOn()
    {
        if ($tick = $this->predis->get(self::TICK_KEY)) {
            return true;
        }

        return false;
    }

    /**
     * @return null
     */
    public function enableUpdateTick()
    {
        $this->predis->set(self::TICK_KEY, 1);
    }

    /**
     * @return null
     */
    public function clearUpdateTick()
    {
        $this->predis->del(self::TICK_KEY);
    }
}
