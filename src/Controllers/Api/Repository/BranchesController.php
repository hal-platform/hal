<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Repository;

use Closure;
use QL\Hal\Api\Normalizer\RepositoryNormalizer;
use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Repository;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Services\GithubService;
use QL\HttpProblem\HttpProblemException;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * API Repository Branches Controller
 */
class BranchesController
{
    use HypermediaResourceTrait;

    /**
     * @var ResponseFormatter
     */
    private $formatter;

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
     * @var RepositoryNormalizer
     */
    private $repositoryNormalizer;

    /**
     * @param ResponseFormatter $formatter
     * @param UrlHelper $url
     * @param GithubService $github
     * @param RepositoryRepository $repositoryRepo
     * @param RepositoryNormalizer $repositoryNormalizer
     */
    public function __construct(
        ResponseFormatter $formatter,
        UrlHelper $url,
        GithubService $github,
        RepositoryRepository $repositoryRepo,
        RepositoryNormalizer $repositoryNormalizer
    ) {
        $this->formatter = $formatter;
        $this->url = $url;
        $this->github = $github;
        $this->repositoryRepo = $repositoryRepo;
        $this->repositoryNormalizer = $repositoryNormalizer;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @throws HttpProblemException
     */
    public function __invoke(Request $request, Response $response, array $params = [])
    {
        $repository = $this->repositoryRepo->findOneBy(['id' => $params['id']]);

        if (!$repository instanceof Repository) {
            throw HttpProblemException::build(404, 'invalid-repository');
        }

        $branches = $this->github->branches($repository->getGithubUser(), $repository->getGithubRepo());
        $status = (count($branches) > 0) ? 200 : 404;

        $branches = array_map(function ($branch) use ($repository) {
            return [
                'name' => $branch['name'],
                'text' => $this->url->formatGitReference($branch['name']),
                'reference' => $branch['name'],
                'commit' => $branch['object']['sha'],
                'url' => $this->url->githubTreeUrl($repository->getGithubUser(), $repository->getGithubRepo(), $branch['name'])
            ];
        }, $branches);

        $this->formatter->respond($this->buildResource(
            [
                'count' => count($branches),
                'branches' => $branches
            ],
            [],
            [
                'repository' => $this->repositoryNormalizer->link($repository)
            ]
        ), $status);
    }
}
