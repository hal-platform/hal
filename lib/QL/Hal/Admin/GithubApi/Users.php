<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Admin\GithubApi;

use QL\Hal\GithubApi\HackUser as GithubUsers;
use QL\Hal\Session;
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
     * @var Session
     */
    private $session;

    /**
     * @param GithubUsers $githubUserService
     * @param Session $session
     */
    public function __construct(GithubUsers $githubUserService, Session $session)
    {
        $this->githubUserService = $githubUserService;
        $this->session = $session;
    }

    /**
     * @param Request $req
     * @param Response $res
     * @return null
     */
    public function __invoke(Request $req, Response $res)
    {
        $users = $this->getUsers();

        $res->header('Content-Type', 'application/json; charset=utf-8');
        $res->body($users);
    }

    /**
     * Falls back to service if cache is unavailable.
     *
     * @return string
     */
    private function getUsers()
    {
        if ($this->session->has('github-users')) {
            return $this->session->get('github-users');
        }

        $users = $this->fetchUsersFromService();

        $users = $this->formatUsers($users);
        $this->session->set('github-users', $users);
        return $users;
    }

    /**
     * Format an array of user arrays into a jsonified object
     *
     * @param array $data
     * @return string
     */
    private function formatUsers(array $data)
    {
        $users = [];
        array_walk($data, function($user) use (&$users) {
            $id = strtolower($user['login']);
            $display = sprintf('%s (%s)', $user['login'], $user['repos']);
            $users[$id] = $display;
        });

        return json_encode($users, JSON_PRETTY_PRINT);
    }

    /**
     * @return array
     */
    private function fetchUsersFromService()
    {
        $users = [];

        // this is so stupid
        // max out at 10 requests so we limit our spamming
        for ($i = 1; $i < 10; $i++) {
            $data = $this->githubUserService->find('repos:>0', $i);
            $users = array_merge($users, $data['users']);
            if (count($data['users']) < 100) {
                break;
            }
        }

        usort($users, function($a, $b) {
            return strcasecmp($a['login'], $b['login']);
        });

        return $users;
    }
}
