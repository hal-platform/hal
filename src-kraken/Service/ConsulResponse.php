<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Service;

use JsonSerializable;

class ConsulResponse implements JsonSerializable
{
    /**
     * @type string
     */
    private $key;

    /**
     * @type string
     */
    private $type;

    /**
     * @type bool
     */
    private $isSuccess;

    /**
     * @type mixed
     */
    private $detail;

    /**
     * @param string $key
     * @param string $type
     */
    public function __construct($key = '', $type = '')
    {
        $this->key = $key;
        $this->type = $type;

        $this->isSuccess = false;
    }

    /**
     * @return string
     */
    public function key()
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function type()
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function detail()
    {
        return $this->detail;
    }

    /**
     * @return bool
     */
    public function isSuccess()
    {
        return $this->isSuccess;
    }

    /**
     * @param mixed $detail
     *
     * @return self
     */
    public function withDetail($detail)
    {
        $this->detail = $detail;
        $this->isSuccess = ($detail === true);

        return $this;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $json = [
            'key' => $this->key(),
            'type' => $this->type(),
            'isSuccess' => $this->isSuccess(),
            'detail' => $this->detail()
        ];

        return $json;
    }
}
