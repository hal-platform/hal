<?php

namespace QL\Hal\Controllers\Api\Repository;

use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Hal\Helpers\UrlHelper;
use Slim\Http\Request;
use Slim\Http\Response;
use QL\Hal\Helpers\ApiHelper;
use QL\Hal\Services\GithubService;
use QL\Hal\Core\Entity\Repository;

/**
 * API Repository Pull Requests Controller
 */
class PullRequestsController
{
    /**
     * @var ApiHelper
     */
    private $api;

    /**
     * @var UrlHelper
     */
    private $url;

    /**
     * @var GithubService
     */
    private $github;

    /**
     * @var RepositoryRepository
     */
    private $repositories;

    /**
     * @param ApiHelper $api
     * @param UrlHelper $url
     * @param GithubService $github
     * @param RepositoryRepository $repositories
     */
    public function __construct(
        ApiHelper $api,
        UrlHelper $url,
        GithubService $github,
        RepositoryRepository $repositories
    ) {
        $this->api = $api;
        $this->url = $url;
        $this->github = $github;
        $this->repositories = $repositories;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        $repository = $this->repositories->findOneBy(['id' => $params['id']]);

        if (!($repository instanceof Repository)) {
            call_user_func($notFound);
            return;
        }

        $links = [
            'self' => ['href' => ['api.repository.pullrequests', ['id' => $repository->getId()]], 'type' => 'Repository Tags'],
            'repository' => ['href' => ['api.repository', ['id' => $repository->getId()]], 'type' => 'Repository'],
            'index' => ['href' => 'api.index']
        ];

        $content = [
            'pullrequests' => [
                'open' => [],
                'closed' => []
            ]
        ];

        $open = $this->github->openPullRequests(
            $repository->getGithubUser(),
            $repository->getGithubRepo()
        );

        $closed = $this->github->closedPullRequests(
            $repository->getGithubUser(),
            $repository->getGithubRepo()
        );

        foreach ($open as $pull) {
            $reference = sprintf('pull/%s', $pull['number']);

            $content['pullrequests']['open'][] = [
                'name' => $pull['title'],
                //'body' => $pull['body'],
                'text' => $this->url->formatGitReference($reference),
                'reference' => $reference,
                'commit' => $pull['head']['sha'],
                'url' => $this->url->githubPullRequestUrl(
                    $repository->getGithubUser(),
                    $repository->getGithubRepo(),
                    $pull['number']
                ),
                'diff' => $pull['diff_url'],
                //'raw' => $pull
            ];
        }

        foreach ($closed as $pull) {
            $reference = sprintf('pull/%s', $pull['number']);

            $content['pullrequests']['closed'][] = [
                'name' => $pull['title'],
                //'body' => $pull['body'],
                'text' => $this->url->formatGitReference($reference),
                'reference' => $reference,
                'commit' => $pull['head']['sha'],
                'url' => $this->url->githubPullRequestUrl(
                    $repository->getGithubUser(),
                    $repository->getGithubRepo(),
                    $pull['number']
                ),
                'diff' => $pull['diff_url'],
                //'raw' => $pull
            ];
        }

        $this->api->prepareResponse($response, $links, $content);
    }
}
