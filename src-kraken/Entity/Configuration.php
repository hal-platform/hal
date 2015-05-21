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
    protected $audit;

    /**
     * @type bool
     */
    protected $isSuccess;

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
        $this->audit = '';
        $this->created = null;

        $this->isSuccess = false;

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
     * @return bool
     */
    public function isSuccess()
    {
        return $this->isSuccess;
    }

    /**
     * @return string
     */
    public function audit()
    {
        return $this->audit;
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
     * @param bool $isSuccess
     *
     * @return self
     */
    public function withIsSuccess($isSuccess)
    {
        $this->isSuccess = (bool) $isSuccess;
        return $this;
    }

    /**
     * @param string $auditData
     *
     * @return self
     */
    public function withAudit($auditData)
    {
        $this->audit = $auditData;
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
            'isSuccess' => $this->isSuccess(),
            'audit' => $this->audit(),
            'created' => $this->created() ? $this->created()->format(DateTime::RFC3339, 'UTC') : null,

            'application' => $this->application(),
            'environment' => $this->environment(),
            'user' => $this->user()
        ];

        return $json;
    }
}
