<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Entity;

use JsonSerializable;

class Property implements JsonSerializable
{
    /**
     * @type string
     */
    protected $id;
    protected $value;

    /**
     * @type PropertySchema
     */
    protected $propertySchema;

    /**
     * @type Application
     */
    protected $application;

    /**
     * @type Environment
     */
    protected $environment;

    public function __construct()
    {
        $this->id = '';
        $this->value = '';

        $this->propertySchema = null;
        $this->application = null;
        $this->environment = null;
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
     * @return PropertySchema
     */
    public function propertySchema()
    {
        return $this->propertySchema;
    }

    /**
     * @return Application
     */
    public function application()
    {
        return $this->application;
    }

    /**
     * @return Environment
     */
    public function environment()
    {
        return $this->environment;
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
     * @param PropertySchema $propertySchema
     *
     * @return self
     */
    public function withPropertySchema(PropertySchema $propertySchema)
    {
        $this->propertySchema = $propertySchema;
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
     * @param Application $environment
     *
     * @return self
     */
    public function withEnvironment(Environment $environment)
    {
        $this->environment = $environment;
        return $this;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $json = [
            'id' => $this->id(),
            'value' => $this->value(),
            'schema' => $this->propertySchema(),

            'application' => $this->application(),
            'environment' => $this->environment()
        ];

        return $json;
    }
}
