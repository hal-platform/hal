<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Repository;

use Closure;
use QL\Hal\Core\Entity\Repository;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Hal\Helpers\ApiHelper;
use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Services\GithubService;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * API Repository Pull Requests Controller
 */
class PullRequestsController
{
    /**
     * @type ApiHelper
     */
    private $api;

    /**
     * @type UrlHelper
     */
    private $url;

    /**
     * @type GithubService
     */
    private $github;

    /**
     * @type RepositoryRepository
     */
    private $repositoryRepo;

    /**
     * @param ApiHelper $api
     * @param UrlHelper $url
     * @param GithubService $github
     * @param RepositoryRepository $repositoryRepo
     */
    public function __construct(
        ApiHelper $api,
        UrlHelper $url,
        GithubService $github,
        RepositoryRepository $repositoryRepo
    ) {
        $this->api = $api;
        $this->url = $url;
        $this->github = $github;
        $this->repositoryRepo = $repositoryRepo;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        $repository = $this->repositoryRepo->findOneBy(['id' => $params['id']]);
        if (!$repository instanceof Repository) {
            return $response->setStatus(404);
        }

        $open = $this->github->openPullRequests($repository->getGithubUser(), $repository->getGithubRepo());
        $closed = $this->github->closedPullRequests($repository->getGithubUser(), $repository->getGithubRepo());

        if (!$open && !$closed) {
            return $response->setStatus(404);
        }

        $content = [
            'open' => array_map($this->formatPullRequests($repository), $open),
            'closed' => array_map($this->formatPullRequests($repository), $closed)
        ];

        $this->api->prepareResponse($response, $content);
    }

    /**
     * @param Repository $repository
     * @return Closure
     */
    private function formatPullRequests(Repository $repository)
    {
        return function($pull) use ($repository) {
            $reference = sprintf('pull/%s', $pull['number']);

            $to = sprintf('%s/%s', $pull['base']['user']['login'], $pull['base']['ref']);
            $from = sprintf('%s/%s', $pull['head']['user']['login'], $pull['head']['ref']);

            return [
                'title' => $pull['title'],
                'text' => $this->url->formatGitReference($reference),
                'reference' => $reference,
                'commit' => $pull['head']['sha'],

                'state' => $pull['state'],
                'from' => strtolower($from),
                'to' => strtolower($to),

                'url' => $this->url->githubTreeUrl($repository->getGithubUser(), $repository->getGithubRepo(), $pull['number']),
                'diff_url' => $pull['diff_url']
            ];
        };
    }
}
