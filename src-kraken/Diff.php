<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Kraken;

use QL\Kraken\Core\Entity\Property;
use QL\Kraken\Core\Entity\Schema;
use QL\Kraken\Core\Entity\Snapshot;

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
     * @type Snapshot
     */
    private $snapshot;

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
     * @return Snapshot
     */
    public function snapshot()
    {
        return $this->snapshot;
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
     * @param Snapshot $snapshot
     *
     * @return self
     */
    public function withSnapshot(Snapshot $snapshot)
    {
        $this->snapshot = $snapshot;
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
