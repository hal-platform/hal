<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Service;

use JsonSerializable;
use QL\Hal\Core\Entity\Application;

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
    private $leadApplications;

    /**
     * List of application IDs that this user can deploy to production
     *
     * @type string[]
     */
    private $prodApplications;

    /**
     * List of application IDs that this user can deploy to non-production
     *
     * @type string[]
     */
    private $nonProdApplications;

    /**
     * @param bool $isPleb
     * @param bool $isLead
     * @param bool $isButtonPusher
     * @param bool $isSuper
     */
    public function __construct(
        $isPleb = false,
        $isLead = false,
        $isButtonPusher = false,
        $isSuper = false
    ) {
        $this->isPleb = $isPleb;
        $this->isLead = $isLead;
        $this->isButtonPusher = $isButtonPusher;
        $this->isSuper = $isSuper;

        $this->leadApplications = [];
        $this->prodApplications = [];
        $this->nonProdApplications = [];
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
    public function leadApplications()
    {
        return $this->leadApplications;
    }

    /**
     * @return array
     */
    public function prodApplications()
    {
        return $this->prodApplications;
    }

    /**
     * @return array
     */
    public function nonProdApplications()
    {
        return $this->nonProdApplications;
    }

    /**
     * @param Application $application
     *
     * @return array
     */
    public function isLeadOfApplication(Application $application)
    {
        $id = $application->id();

        return in_array($id, $this->leadApplications, true);
    }

    /**
     * @param Application $application
     *
     * @return array
     */
    public function canDeployApplicationToProd(Application $application)
    {
        $id = $application->id();

        return in_array($id, $this->prodApplications, true);
    }

    /**
     * @param Application $application
     *
     * @return array
     */
    public function canDeployApplicationToNonProd(Application $application)
    {
        $id = $repository->id();

        return in_array($id, $this->nonProdApplications, true);
    }

    /**
     * @param string[] $applications
     *
     * @return self
     */
    public function withLeadApplications(array $applications)
    {
        $this->leadApplications = $applications;
        return $this;
    }

    /**
     * @param string[] $applications
     *
     * @return self
     */
    public function withProdApplications(array $applications)
    {
        $this->prodApplications = $applications;
        return $this;
    }

    /**
     * @param string[] $applications
     *
     * @return self
     */
    public function withNonProdApplications(array $applications)
    {
        $this->nonProdApplications = $applications;
        return $this;
    }

    /**
     * @param array $data
     *
     * @return self
     */
    public static function fromSerialized(array $data)
    {
        $perm = new self(
            array_key_exists('isPleb', $data) ? $data['isPleb'] : false,
            array_key_exists('isLead', $data) ? $data['isLead'] : false,
            array_key_exists('isButtonPusher', $data) ? $data['isButtonPusher'] : false,
            array_key_exists('isSuper', $data) ? $data['isSuper'] : false
        );

        $perm->withLeadApplications(array_key_exists('leadApplications', $data) ? $data['leadApplications'] : []);
        $perm->withProdApplications(array_key_exists('prodApplications', $data) ? $data['prodApplications'] : []);
        $perm->withNonProdApplications(array_key_exists('nonProdApplications', $data) ? $data['nonProdApplications'] : []);

        return $perm;
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
            'leadApplications' => $this->leadApplications(),
            'prodApplications' => $this->prodApplications(),
            'nonProdApplications' => $this->nonProdApplications(),
        ];

        return $json;
    }
}
