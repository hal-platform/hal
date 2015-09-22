<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Github;

use Github\Api\Organization as OrganizationApi;

class Organization extends OrganizationApi
{
    /**
     * Request all organizations:
     *
     * @link https://developer.github.com/v3/orgs/#list-all-organizations
     *
     * @return array list of organizations found
     */
    public function all()
    {
        $result = $this->get('organizations');

        return $result;
    }
}
