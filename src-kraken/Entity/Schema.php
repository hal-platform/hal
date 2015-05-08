<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Entity;

use DateTime;
use JsonSerializable;
use MCP\DataType\Time\Timepoint;
use QL\Hal\Core\Entity\User;

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
     * @type Timepoint|null
     */
    protected $created;

    /**
     * @type bool
     */
    protected $isSecure;

    /**
     * @type Application
     */
    protected $application;

    /**
     * @type User|null
     */
    protected $user;

    public function __construct()
    {
        $this->id = '';
        $this->key = '';

        $this->dataType = '';
        $this->description = '';
        $this->isSecure = static::DEFAULT_IS_SECURE;

        $this->created = null;
        $this->application = null;
        $this->user = null;
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
     * @return Timepoint|null
     */
    public function created()
    {
        return $this->created;
    }

    /**
     * @return Application
     */
    public function application()
    {
        return $this->application;
    }

    /**
     * @return User|null
     */
    public function user()
    {
        return $this->user;
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
     * @param Timepoint $created
     *
     * @return self
     */
    public function withCreated(Timepoint $created)
    {
        $this->created = $created;
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
     * @param User $user
     *
     * @return self
     */
    public function withUser(User $user)
    {
        $this->user = $user;
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

            'created' => $this->created() ? $this->created()->format(DateTime::RFC3339, 'UTC') : null,

            'application' => $this->application(),
            'user' => $this->user()
        ];

        return $json;
    }
}
