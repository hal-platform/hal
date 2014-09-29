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
 * API Repository Diff Controller
 *
 * This route is currently disabled.
 */
class DiffController
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

        $diff = $this->github->diff(
            $repository->getGithubUser(),
            $repository->getGithubRepo(),
            $params['base'],
            $params['head']
        );

        $links = [
            'self' => ['href' => ['api.repository.diff', ['id' => $repository->getId(), 'base' => $params['base'], 'head' => $params['head']]], 'type' => 'Repository Diff'],
            'repository' => ['href' => ['api.repository', ['id' => $repository->getId()]], 'type' => 'Repository'],
            'index' => ['href' => 'api.index']
        ];

        $content = [
            'base' => [
                'text' => $params['base'],
                'url' => $this->url->githubCommitUrl($repository->getGithubUser(), $repository->getGithubRepo(), $params['base'])
            ],
            'head' => [
                'text' => $params['head'],
                'url' => $this->url->githubCommitUrl($repository->getGithubUser(), $repository->getGithubRepo(), $params['head'])
            ],
            'url' => [
                'html' => $diff['html_url'],
                'diff' => $diff['diff_url']
            ],
            'stats' => [
                'status' => $diff['status'],
                'ahead' => $diff['ahead_by'],
                'behind' => $diff['behind_by'],
                'commits' => $diff['total_commits']
            ],
            'files' => [],
            //'raw' => $diff
        ];

        $additions = $deletions = $changes =  0;

        foreach ($diff['files'] as $file) {
            $content['files'][] = [
                'file' => $file['filename'],
                'status' => $file['status'],
                'additions' => $file['additions'],
                'deletions' => $file['deletions'],
                'changes' => $file['changes'],
                'url' => $file['blob_url'],
                'patch' => (isset($file['patch'])) ? $file['patch'] : null
            ];

            // stat counter
            $additions += $file['additions'];
            $deletions += $file['deletions'];
            $changes += $file['changes'];
        }

        $content['stats']['additions'] = $additions;
        $content['stats']['deletions'] = $deletions;
        $content['stats']['changes'] = $changes;

        $this->api->prepareResponse($response, $content);
    }
}
