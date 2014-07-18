<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Repository;

use QL\Hal\Core\Entity\Repository;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Hal\Helpers\ApiHelper;
use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Services\GithubService;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * API Repository Tags Controller
 */
class TagsController
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
     */
    public function __invoke(Request $request, Response $response, array $params = [])
    {
        $repository = $this->repositoryRepo->findOneBy(['id' => $params['id']]);
        if (!$repository instanceof Repository) {
            return $response->setStatus(404);
        }

        $tags = $this->github->tags($repository->getGithubUser(), $repository->getGithubRepo());
        if (!$tags) {
            return $response->setStatus(404);
        }

        $content = array_map(function($tag) use ($repository) {
            $reference = sprintf('tag/%s', $tag['name']);
            return [
                'name' => $tag['name'],
                'text' => $this->url->formatGitReference($reference),
                'reference' => $reference,
                'commit' => $tag['object']['sha'],
                'url' => $this->url->githubTreeUrl($repository->getGithubUser(), $repository->getGithubRepo(), $tag['name'])
            ];
        }, $tags);

        $this->api->prepareResponse($response, $content);
    }
}
