<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken;

use QL\Kraken\Entity\ConfigurationProperty;
use QL\Kraken\Entity\Property;
use QL\Kraken\Entity\Schema;

class Diff
{
    /**
     * @type string
     */
    private $key;

    /**
     * @type Schema
     */
    private $schema;

    /**
     * @type Property
     */
    private $property;

    /**
     * @type ConfigurationProperty
     */
    private $configuration;

    /**
     * @type bool
     */
    private $isChanged;

    /**
     * @param string $key
     */
    public function __construct($key = '')
    {
        $this->key = $key;
        $this->isChanged = false;
    }

    /**
     * @return string
     */
    public function key()
    {
        return $this->key;
    }

    /**
     * @return Schema
     */
    public function schema()
    {
        return $this->schema;
    }

    /**
     * @return Property
     */
    public function property()
    {
        return $this->property;
    }

    /**
     * @return ConfigurationProperty
     */
    public function configuration()
    {
        return $this->configuration;
    }

    /**
     * @return bool
     */
    public function isChanged()
    {
        return $this->isChanged;
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
     * @param ConfigurationProperty $configuration
     *
     * @return self
     */
    public function withConfiguration(ConfigurationProperty $configuration)
    {
        $this->configuration = $configuration;
        return $this;
    }

    /**
     * @param bool $isChanged
     *
     * @return self
     */
    public function withIsChanged($isChanged)
    {
        $this->isChanged = (bool) $isChanged;
        return $this;
    }

}
