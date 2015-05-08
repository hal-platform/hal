<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Entity;

use DateTime;
use JsonSerializable;
use MCP\DataType\Time\TimePoint;
use QL\Hal\Core\Entity\User;

class Configuration implements JsonSerializable
{
    /**
     * @type string
     */
    protected $id;
    protected $configuration;
    protected $checksum;

    /**
     * @type Timepoint|null
     */
    protected $created;

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
        $this->configuration = '';
        $this->checksum = '';
        $this->created = null;

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
    public function configuration()
    {
        return $this->configuration;
    }

    /**
     * @return string
     */
    public function checksum()
    {
        return $this->checksum;
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
     * @param string $configuration
     *
     * @return self
     */
    public function withConfiguration($configuration)
    {
        $this->configuration = $configuration;
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
            'configuration' => $this->configuration(),
            'checksum' => $this->checksum(),
            'created' => $this->created() ? $this->created()->format(DateTime::RFC3339, 'UTC') : null,

            'application' => $this->application(),
            'environment' => $this->environment(),
            'user' => $this->user()
        ];

        return $json;
    }
}
