<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Entity;

use JsonSerializable;

class Target implements JsonSerializable
{
    /**
     * @type string
     */
    protected $id;
    protected $key;

    /**
     * @type Application
     */
    protected $application;

    /**
     * @type Environment
     */
    protected $environment;

    /**
     * @type Configuration
     */
    protected $configuration;

    public function __construct()
    {
        $this->id = '';
        $this->key = '';

        $this->application = null;
        $this->environment = null;
        $this->configuration = null;
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
     * @return Configuration
     */
    public function configuration()
    {
        return $this->configuration;
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
     * @return array
     */
    public function jsonSerialize()
    {
        $json = [
            'id' => $this->id(),
            'application' => $this->application(),
            'environment' => $this->environment(),
            'configuration' => $this->configuration()
        ];

        return $json;
    }
}
