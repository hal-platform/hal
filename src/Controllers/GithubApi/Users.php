<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\GithubApi;

use QL\Hal\Services\GithubService;
use QL\Panthor\ControllerInterface;
use Slim\Http\Response;

/**
 * @deprecated maybe?
 */
class Users implements ControllerInterface
{
    /**
     * @type GithubService
     */
    private $github;

    /**
     * @type Response
     */
    private $response;

    /**
     * @param GithubService $github
     * @param Response $response
     */
    public function __construct(GithubService $github, Response $response)
    {
        $this->github = $github;
        $this->response = $response;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $users = $this->github->users();

        $this->response->headers->set('Content-Type', 'application/json; charset=utf-8');
        $this->response->setBody($this->formatUsersAndOrganizations($users));
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
}
