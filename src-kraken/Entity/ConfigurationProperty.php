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

class ConfigurationProperty implements JsonSerializable
{
    /**
     * @type string
     */
    protected $id;
    protected $value;
    protected $key;
    protected $dataType;

    /**
     * @type bool
     */
    protected $isSecure;

    /**
     * @type Timepoint|null
     */
    protected $created;

    /**
     * @type Configuration
     */
    protected $configuration;

    /**
     * @type Property|null
     */
    protected $property;

    /**
     * @type Schema|null
     */
    protected $schema;

    /**
     * @type User|null
     */
    protected $user;

    public function __construct()
    {
        $this->id = '';
        $this->value = '';
        $this->key = '';
        $this->dataType = '';

        $this->isSecure = Schema::DEFAULT_IS_SECURE;

        $this->created = null;
        $this->configuration = null;
        $this->property = null;
        $this->schema = null;
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
    public function value()
    {
        return $this->value;
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
     * @return Configuration
     */
    public function configuration()
    {
        return $this->configuration;
    }

    /**
     * @return Property|null
     */
    public function property()
    {
        return $this->property;
    }

    /**
     * @return Schema|null
     */
    public function schema()
    {
        return $this->schema;
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
     * @param string $value
     *
     * @return self
     */
    public function withValue($value)
    {
        $this->value = $value;
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
     * @param Configuration $configuration
     *
     * @return self
     */
    public function withConfiguration(Configuration $configuration)
    {
        $this->configuration = $configuration;
        return $this;
    }

    /**
     * @param Property $property
     *
     * @return self
     */
    public function withProperty(Property $property)
    {
        $this->property = $property;
        return $this;
    }

    /**
     * @param Schema $schema
     *
     * @return self
     */
    public function withSchema(Schema $schema)
    {
        $this->schema = $schema;
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
            'value' => $this->value(),

            'isSecure' => $this->isSecure(),

            'created' => $this->created() ? $this->created()->format(DateTime::RFC3339, 'UTC') : null,

            'configuration' => $this->configuration(),
            'property' => $this->property(),
            'schema' => $this->schema(),
            'user' => $this->user()
        ];

        return $json;
    }
}
