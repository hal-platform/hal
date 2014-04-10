<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Admin\GithubApi;

use Github\ResultPager;
use QL\Hal\GithubApi\HackUser as GithubUsers;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * @api
 */
class Users
{
    /**
     * @var GithubUsers
     */
    private $githubUserService;

    /**
     * @var ResultPager
     */
    private $pager;

    /**
     * @param GithubUsers $githubUserService
     * @param ResultPager $pager
     */
    public function __construct(GithubUsers $githubUserService, ResultPager $pager)
    {
        $this->githubUserService = $githubUserService;
        $this->pager = $pager;
    }

    /**
     * @param Request $req
     * @param Response $res
     * @return null
     */
    public function __invoke(Request $req, Response $res)
    {
        $users = $this->fetchUsersFromService();

        $res->header('Content-Type', 'application/json; charset=utf-8');
        $res->body($this->formatUsersAndOrganizations($users));
    }

    /**
     * Format the raw list of users into separate user and organization lists.
     *
     * @param array $data
     * @return string
     */
    private function formatUsersAndOrganizations(array $data)
    {
        $users = [];
        $organizations = [];

        usort($data, function($a, $b) {
            return strcasecmp($a['login'], $b['login']);
        });

        array_walk($data, function($user) use (&$users, &$organizations) {
            $id = strtolower($user['login']);
            $display = $user['login'];

            if ($user['type'] == 'Organization') {
                $organizations[$id] = $display;
            } else {
                $users[$id] = $display;
            }
        });

        $data = ['users' => $users, 'organizations' => $organizations];
        return json_encode($data, JSON_PRETTY_PRINT);
    }

    /**
     * @return array
     */
    private function fetchUsersFromService()
    {
        $users = $this->pager->fetchAll($this->githubUserService, 'all');

        return $users;
    }
}
