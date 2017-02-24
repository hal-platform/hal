<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\ApiInternal;

use Hal\UI\Service\GitHubService;
use QL\Panthor\ControllerInterface;
use Slim\Http\Response;

/**
 * @deprecated maybe?
 */
class GitHubReposController implements ControllerInterface
{
    /**
     * @var GitHubService
     */
    private $github;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var array
     */
    private $parameters;

    /**
     * @param GitHubService $github
     * @param Response $response
     * @param array $parameters
     */
    public function __construct(GitHubService $github, Response $response, array $parameters)
    {
        $this->github = $github;
        $this->response = $response;
        $this->parameters = $parameters;
    }

    /**
     * @inheritDoc
     */
    public function __invoke()
    {
        if (!isset($this->parameters['username'])) {
            $this->response->setStatus(404);
            return;
        }

        if (!$this->parameters['username']) {
            $this->response->setStatus(400);
            return;
        }

        if (!$repos = $this->fetchReposFromService($this->parameters['username'])) {
            $this->response->setStatus(404);
            return;
        }

        $repos = $this->formatRepos($repos);

        $this->response->headers->set('Content-Type', 'application/json; charset=utf-8');
        $this->response->setBody($repos);
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
        $repos = $this->github->repositories($user);
        usort($repos, function($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        return $repos;
    }
}