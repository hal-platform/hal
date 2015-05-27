<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Entity;

use JsonSerializable;
use QL\Hal\Core\Entity\Repository as HalApplication;

class Application implements JsonSerializable
{
    /**
     * @type string
     */
    protected $id;
    protected $name;
    protected $coreId;

    /**
     * @type HalApplication|null
     */
    protected $halApplication;

    public function __construct()
    {
        $this->id = '';
        $this->name = '';
        $this->coreId = '';

        $this->halApplication = null;
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
    public function name()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function coreId()
    {
        return $this->coreId;
    }

    /**
     * @return HalApplication|null
     */
    public function halApplication()
    {
        return $this->halApplication;
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
     * @param string $name
     *
     * @return self
     */
    public function withName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @param string $coreId
     *
     * @return self
     */
    public function withCoreId($coreId)
    {
        $this->coreId = $coreId;
        return $this;
    }

    /**
     * @param HalApplication|null $application
     *
     * @return self
     */
    public function withHalApplication(HalApplication $application = null)
    {
        $this->halApplication = $application;
        return $this;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $json = [
            'id' => $this->id(),
            'name' => $this->name(),
            'coreId' => $this->coreId(),

            'halApplication' => $this->halApplication() ? $this->halApplication()->getId() : null
        ];

        return $json;
    }
}
