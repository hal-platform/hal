<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Service;

use JsonSerializable;

class UserPerm implements JsonSerializable
{
    /**
     * @type bool
     */
    private $isPleb;

    /**
     * @type bool
     */
    private $isLead;

    /**
     * @type bool
     */
    private $isButtonPusher;

    /**
     * @type bool
     */
    private $isSuper;

    /**
     * List of application IDs that this user is lead for.
     *
     * @type string[]
     */
    private $applications;

    /**
     * @param bool $isPleb
     * @param bool $isLead
     * @param bool $isButtonPusher
     * @param bool $isSuper
     * @param string[] $applications
     */
    public function __construct($isPleb = false, $isLead = false, $isButtonPusher = false, $isSuper = false, $applications = [])
    {
        $this->isPleb = $isPleb;
        $this->isLead = $isLead;
        $this->isButtonPusher = $isButtonPusher;
        $this->isSuper = $isSuper;

        $this->applications = $applications;
    }

    /**
     * @return bool
     */
    public function isPleb()
    {
        return $this->isPleb;
    }

    /**
     * @return bool
     */
    public function isLead()
    {
        return $this->isLead;
    }

    /**
     * @return bool
     */
    public function isButtonPusher()
    {
        return $this->isButtonPusher;
    }

    /**
     * @return bool
     */
    public function isSuper()
    {
        return $this->isSuper;
    }

    /**
     * @return array
     */
    public function applications()
    {
        return $this->applications;
    }

    /**
     * @param Repository $repository
     *
     * @return array
     */
    public function isLeadOfApplication(Repository $repository)
    {
        $id = $repository->getId();

        return in_array($id, $this->applications, true);
    }

    /**
     * @param array $data
     *
     * @return self
     */
    public static function fromSerialized(array $data)
    {
        return new self(
            array_key_exists('isPleb', $data) ? $data['isPleb'] : false,
            array_key_exists('isLead', $data) ? $data['isLead'] : false,
            array_key_exists('isButtonPusher', $data) ? $data['isButtonPusher'] : false,
            array_key_exists('isSuper', $data) ? $data['isSuper'] : false,
            array_key_exists('applications', $data) ? $data['applications'] : []
        );
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $json = [
            'isPleb' => $this->isPleb(),
            'isLead' => $this->isLead(),
            'isButtonPusher' => $this->isButtonPusher(),
            'isSuper' => $this->isSuper(),
            'applications' => $this->applications()
        ];

        return $json;
    }
}
