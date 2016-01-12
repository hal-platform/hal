<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Kraken\Service;

use JsonSerializable;

class ConsulResponse implements JsonSerializable
{
    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $type;

    /**
     * @var bool
     */
    private $isSuccess;

    /**
     * @var mixed
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
