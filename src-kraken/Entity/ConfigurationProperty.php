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

/**
 * This is a denormalized combination of the Schema + Property.
 *
 * This allows us to verify what is currently deployed (with checksums), or
 * redeploy/rollback an previous configuration. Even if the schema was changed.
 */
class ConfigurationProperty implements JsonSerializable
{
    /**
     * @type string
     */
    protected $id;
    protected $key;
    protected $value;
    protected $dataType;
    protected $checksum;

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

    public function __construct()
    {
        $this->id = '';
        $this->key = '';
        $this->value = '';
        $this->dataType = '';
        $this->checksum = '';

        $this->isSecure = Schema::DEFAULT_IS_SECURE;

        $this->created = null;
        $this->configuration = null;
        $this->property = null;
        $this->schema = null;
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
    public function value()
    {
        return $this->value;
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
    public function checksum()
    {
        return $this->checksum;
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
     * @param string $checksum
     *
     * @return self
     */
    public function withChecksum($checksum)
    {
        $this->checksum = $checksum;
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
     * @return array
     */
    public function jsonSerialize()
    {
        $json = [
            'id' => $this->id(),
            'key' => $this->key(),
            'value' => $this->value(),
            'dataType' => $this->dataType(),
            'checksum' => $this->checksum(),

            'isSecure' => $this->isSecure(),

            'created' => $this->created() ? $this->created()->format(DateTime::RFC3339, 'UTC') : null,

            'configuration' => $this->configuration(),
            'property' => $this->property(),
            'schema' => $this->schema()
        ];

        return $json;
    }
}
