<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Services\ElasticBeanstalk;

/**
 * Immutable Data model for an elastic beanstalk environment
 */
class Environment
{
    /**
     * @type string
     */
    private $id;
    private $name;

    /**
     * @type string
     *
     * Status:
     *     - Launching
     *     - Updating
     *     - Ready
     *     - Terminating
     *     - Terminated
     */
    private $status;

    /**
     * @type string
     *
     * Health:
     *     - Red
     *     - Yellow
     *     - Green
     *     - Grey
     */
    private $health;

    /**
     * @type string
     */
    private $applicationName;
    private $currentVersion;
    private $solution;
    private $url;

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        $properties = array_keys(get_object_vars($this));
        foreach ($properties as $property) {
            if (isset($data[$property])) {
                $this->$property = $data[$property];
            }
        }
    }

    /**
     * @return string
     */
    public function __call($name, array $arguments)
    {
        if (!property_exists($this, $name)) {
            return null;
        }

        return $this->$name;
    }
}
