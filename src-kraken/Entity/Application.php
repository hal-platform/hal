<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Entity;

use JsonSerializable;
use QL\Hal\Core\Entity\Repository;

class Application implements JsonSerializable
{
    /**
     * @type string
     */
    protected $id;
    protected $name;
    protected $coreId;

    /**
     * @type Repository|null
     */
    protected $halRepository;

    public function __construct()
    {
        $this->id = '';
        $this->name = '';
        $this->coreId = '';

        $this->halRepository = null;
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
     * @return string
     */
    public function halRepository()
    {
        return $this->halRepository;
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
     * @param string $repository
     *
     * @return self
     */
    public function withHalRepository(Repository $repository)
    {
        $this->halRepository = $repository;
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

            'halRepository' => $this->halRepository() ? $this->halRepository()->getId() : null
        ];

        return $json;
    }
}
