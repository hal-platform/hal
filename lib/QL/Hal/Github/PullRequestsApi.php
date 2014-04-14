<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Github;

use QL\Hal\Services\GithubService;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * @api
 */
class PullRequestsApi
{
    /**
     * @var GithubService
     */
    private $github;

    /**
     * @param GithubService $github
     */
    public function __construct(GithubService $github)
    {
        $this->github = $github;
    }

    /**
     * @param Request $req
     * @param Response $res
     * @param array $params
     * @return null
     */
    public function __invoke(Request $req, Response $res, array $params = [])
    {
        if (!isset($params['username']) || !isset($params['repository']) ) {
            $res->setStatus(404);
            return;
        }

        if (!$params['username'] || !$params['repository']) {
            $res->setStatus(400);
            return;
        }

        if (!$pulls = $this->fetchFromService($params['username'], $params['repository'])) {
            $res->setStatus(404);
            return;
        }

        $pulls = $this->formatPulls($pulls);

        $res->header('Content-Type', 'application/json; charset=utf-8');
        $res->body($pulls);
    }

    /**
     * Cut down the data to only what we need and format as json.
     *
     * @param array $data
     * @return string
     */
    private function formatPulls(array $data)
    {
        array_walk($data, function(&$pull) {
            $to = sprintf('%s/%s', $pull['base']['user']['login'], $pull['base']['ref']);
            $from = sprintf('%s/%s', $pull['head']['user']['login'], $pull['head']['ref']);

            $pull = [
                'from' => strtolower($from),
                'number' => $pull['number'],
                'state' => $pull['state'],
                'title' => $pull['title'],
                'to' => strtolower($to),
                'url' => $pull['html_url']
            ];
        });

        return json_encode($data, JSON_PRETTY_PRINT);
    }

    /**
     * @param string $user
     * @param string $repo
     * @return array
     */
    private function fetchFromService($user, $repo)
    {
        $pulls = $this->github->openPullRequests($user, $repo);
        $pulls = array_merge($pulls, $this->github->closedPullRequests($user, $repo));

        usort($pulls, function($a, $b) {
            return ($a['number'] < $b['number']);
        });

        return $pulls;
    }
}
