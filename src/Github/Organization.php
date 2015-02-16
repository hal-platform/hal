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
     * @link https://developer.github.com/v3/search/#search-users
     *
     * @return array list of organizations found
     */
    public function all()
    {
        $result = $this->get('search/users', [
            'q' => 'type:organization'
        ]);

        return $result['items'];
    }
}
