<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\GithubApi;

use Github\Api\User as BaseUser;

/**
 * Extending the knplabs api to provide additional functionality.
 *
 * @internal
 */
class HackUser extends BaseUser
{
    public function all()
    {
        return $this->get('users');
    }
}
