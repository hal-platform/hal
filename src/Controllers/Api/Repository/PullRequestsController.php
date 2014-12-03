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
 * API Repository Pull Requests Controller
 */
class PullRequestsController
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
     * @param callable $notFound
     * @throws HttpProblemException
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        $repository = $this->repositoryRepo->findOneBy(['id' => $params['id']]);

        if (!$repository instanceof Repository) {
            throw HttpProblemException::build(404, 'invalid-repository');
        }

        $open = $this->github->openPullRequests($repository->getGithubUser(), $repository->getGithubRepo());
        $closed = $this->github->closedPullRequests($repository->getGithubUser(), $repository->getGithubRepo());
        $status = (count($open) > 0 || count($closed) > 0) ? 200 : 404;

        $this->formatter->respond($this->buildResource(
            [
                'open' => array_map($this->formatPullRequests($repository), $open),
                'closed' => array_map($this->formatPullRequests($repository), $closed)
            ],
            [],
            [
                'repository' => $this->repositoryNormalizer->link($repository)
            ]
        ), $status);
    }

    /**
     * @param Repository $repository
     * @return Closure
     */
    private function formatPullRequests(Repository $repository)
    {
        return function ($pull) use ($repository) {
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

                'url' => $this->url->githubPullRequestUrl($repository->getGithubUser(), $repository->getGithubRepo(), $pull['number']),
                'diff_url' => $pull['diff_url']
            ];
        };
    }
}
