<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Admin\GithubApi;

use Github\Exception\RuntimeException as GithubException;
use QL\Hal\GithubApi\HackUser as GithubUsers;
use QL\Hal\Session;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * @api
 */
class Repos
{
    /**
     * @var string
     */
    const CACHE_KEY_TEMPLATE = 'github-repos-%s';

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

        if (!$repos = $this->getRepos($params['username'])) {
            $res->setStatus(404);
            return;
        }

        $res->header('Content-Type', 'application/json; charset=utf-8');
        $res->body($repos);
    }

    /**
     * Falls back to service if cache is unavailable.
     *
     * @param string $user
     * @return string|null
     */
    private function getRepos($user)
    {
        $cacheKey = sprintf(self::CACHE_KEY_TEMPLATE, $user);

        if ($this->session->has($cacheKey)) {
            return $this->session->get($cacheKey);
        }

        $repos = $this->fetchReposFromService($user);

        $repos = $this->formatRepos($repos);
        $this->session->set($cacheKey, $repos);
        return $repos;
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
