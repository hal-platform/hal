<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Entity;

use JsonSerializable;

class Schema implements JsonSerializable
{
    const DEFAULT_IS_SECURE = true;

    /**
     * @type string
     */
    protected $id;
    protected $key;
    protected $dataType;
    protected $description;

    /**
     * @type bool
     */
    protected $isSecure;

    /**
     * @type Application
     */
    protected $application;

    public function __construct()
    {
        $this->id = '';
        $this->key = '';

        $this->dataType = '';
        $this->description = '';
        $this->isSecure = static::DEFAULT_IS_SECURE;

        $this->application = null;
    }

    /**
     * @return string
     */
    public function id()
    {
        return $this->id;
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
    public function dataType()
    {
        return $this->dataType;
    }

    /**
     * @return string
     */
    public function description()
    {
        return $this->description;
    }

    /**
     * @return bool
     */
    public function isSecure()
    {
        return $this->isSecure;
    }

    /**
     * @return Application
     */
    public function application()
    {
        return $this->application;
    }

    /**
     * @param string $id
     *
     * @return self
     */
    public function withId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @param string $key
     *
     * @return self
     */
    public function withKey($key)
    {
        $this->key = $key;
        return $this;
    }

    /**
     * @param string $dataType
     *
     * @return self
     */
    public function withDataType($dataType)
    {
        $this->dataType = $dataType;
        return $this;
    }

    /**
     * @param string $description
     *
     * @return self
     */
    public function withDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @param bool $isSecure
     *
     * @return self
     */
    public function withIsSecure($isSecure)
    {
        $this->isSecure = (bool) $isSecure;
        return $this;
    }

    /**
     * @param Application $application
     *
     * @return self
     */
    public function withApplication(Application $application)
    {
        $this->application = $application;
        return $this;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $json = [
            'id' => $this->id(),
            'key' => $this->key(),
            'dataType' => $this->dataType(),
            'description' => $this->description(),

            'isSecure' => $this->isSecure(),

            'application' => $this->application()
        ];

        return $json;
    }
}
