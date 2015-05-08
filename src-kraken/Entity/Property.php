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

class Property implements JsonSerializable
{
    /**
     * @type string
     */
    protected $id;
    protected $value;

    /**
     * @type Timepoint|null
     */
    protected $created;

    /**
     * @type Schema
     */
    protected $schema;

    /**
     * @type Application
     */
    protected $application;

    /**
     * @type Environment
     */
    protected $environment;

    /**
     * @type User|null
     */
    protected $user;

    public function __construct()
    {
        $this->id = '';
        $this->value = '';

        $this->created = null;
        $this->schema = null;
        $this->application = null;
        $this->environment = null;
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
     * @return Timepoint|null
     */
    public function created()
    {
        return $this->created;
    }

    /**
     * @return Schema
     */
    public function schema()
    {
        return $this->schema;
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
            'value' => $this->value(),
            'schema' => $this->schema(),

            'created' => $this->created() ? $this->created()->format(DateTime::RFC3339, 'UTC') : null,

            'application' => $this->application(),
            'environment' => $this->environment(),
            'user' => $this->user()
        ];

        return $json;
    }
}
