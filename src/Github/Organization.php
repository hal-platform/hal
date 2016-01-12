<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
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
