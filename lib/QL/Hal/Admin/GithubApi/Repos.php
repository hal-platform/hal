<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Admin\GithubApi;

use Github\Exception\RuntimeException as GithubException;
use QL\Hal\GithubApi\HackUser as GithubUsers;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * @api
 */
class Repos
{
    /**
     * @var GithubUsers
     */
    private $githubUserService;

    /**
     * @param GithubUsers $githubUserService
     */
    public function __construct(GithubUsers $githubUserService)
    {
        $this->githubUserService = $githubUserService;
    }

    /**
     * @param Request $req
     * @param Response $res
     * @param array $params
     * @return null
     */
    public function __invoke(Request $req, Response $res, array $params = [])
    {
        if (!isset($params['username'])) {
            $res->setStatus(404);
            return;
        }

        if (!$params['username']) {
            $res->setStatus(400);
            return;
        }

        if (!$repos = $this->fetchReposFromService($params['username'])) {
            $res->setStatus(404);
            return;
        }

        $repos = $this->formatRepos($repos);

        $res->header('Content-Type', 'application/json; charset=utf-8');
        $res->body($repos);
    }

    /**
     * Format an array of repo arrays into a jsonified object
     *
     * @param array $data
     * @return string|null
     */
    private function formatRepos(array $data)
    {
        if (!$data) {
            return null;
        }

        $repos = [];
        array_walk($data, function($repository) use (&$repos) {
            $id = strtolower($repository['name']);
            $display = $id;
            if ($repository['description']) {
                $chop = $repository['description'];
                if (strlen($chop) > 50) {
                    $chop = substr($chop, 0, 47) . '...';
                }

                $display = sprintf('%s (%s)', $repository['name'], $chop);
            }
            $repos[$id] = $display;
        });

        return json_encode($repos, JSON_PRETTY_PRINT);
    }

    /**
     * @param string $user
     * @return array
     */
    private function fetchReposFromService($user)
    {
        try {
            // I'm going to ASSume we dont need to worry about pagination here
            $repos = $this->githubUserService->repositories($user);
        } catch (GithubException $e) {
            return [];
        }

        usort($repos, function($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        return $repos;
    }
}
