<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Entity;

use JsonSerializable;

class Configuration implements JsonSerializable
{
    /**
     * @type string
     */
    protected $id;
    protected $configuration;
    protected $checksum;

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
        $this->configuration = '';
        $this->checksum = '';

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
            'configuration' => $this->configuration(),
            'checksum' => $this->checksum(),

            'application' => $this->application(),
            'environment' => $this->environment()
        ];

        return $json;
    }
}
