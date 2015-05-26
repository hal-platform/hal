<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Entity;

use JsonSerializable;

class Environment implements JsonSerializable
{
    /**
     * @type string
     */
    protected $id;
    protected $name;
    protected $consulServer;
    protected $consulToken;

    /**
     * @type string
     */
    protected $isProduction;

    public function __construct()
    {
        $this->id = '';
        $this->name = '';
        $this->consulServer = '';
        $this->consulToken = '';

        $this->isProduction = false;
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
     * @return bool
     */
    public function isProduction()
    {
        return $this->isProduction;
    }

    /**
     * @return string
     */
    public function consulServer()
    {
        return $this->consulServer;
    }

    /**
     * @return string
     */
    public function consulToken()
    {
        return $this->consulToken;
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
     * @param bool $isProduction
     *
     * @return self
     */
    public function withIsProduction($isProduction)
    {
        $this->isProduction = $isProduction;
        return $this;
    }

    /**
     * @param string $server
     *
     * @return self
     */
    public function withConsulServer($server)
    {
        $this->consulServer = $server;
        return $this;
    }

    /**
     * @param string $token
     *
     * @return self
     */
    public function withConsulToken($token)
    {
        $this->consulToken = $token;
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
            'isProduction' => $this->isProduction(),

            'consulServer' => $this->consulServer(),
            'consulToken' => $this->consulToken()
        ];

        return $json;
    }
}
