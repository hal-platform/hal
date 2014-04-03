<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\GithubApi;

use Github\Api\User as BaseUser;

/**
 * This replacement is necessary because our enterprise github does not support v3 search or proper pagination.
 *
 * @internal
 */
class HackUser extends BaseUser
{
    public function find($keyword, $page = 1)
    {
        return $this->get('legacy/user/search/'.rawurlencode($keyword), [
            'start_page' => $page
        ]);
    }
}
