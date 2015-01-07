<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Repository;

use QL\Hal\Api\Normalizer\RepositoryNormalizer;
use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Repository;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Services\GithubService;
use QL\HttpProblem\HttpProblemException;
use QL\Panthor\ControllerInterface;

class TagsController implements ControllerInterface
{
    use HypermediaResourceTrait;

    /**
     * @type ResponseFormatter
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
     * @type RepositoryNormalizer
     */
    private $repositoryNormalizer;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param ResponseFormatter $formatter
     * @param UrlHelper $url
     * @param GithubService $github
     * @param RepositoryRepository $repositoryRepo
     * @param RepositoryNormalizer $repositoryNormalizer
     * @param array $parameters
     */
    public function __construct(
        ResponseFormatter $formatter,
        UrlHelper $url,
        GithubService $github,
        RepositoryRepository $repositoryRepo,
        RepositoryNormalizer $repositoryNormalizer,
        array $parameters
    ) {
        $this->formatter = $formatter;
        $this->url = $url;
        $this->github = $github;
        $this->repositoryRepo = $repositoryRepo;
        $this->repositoryNormalizer = $repositoryNormalizer;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     * @throws HttpProblemException
     */
    public function __invoke()
    {
        $repository = $this->repositoryRepo->find($this->parameters['id']);

        if (!$repository instanceof Repository) {
            throw HttpProblemException::build(404, 'invalid-repository');
        }

        $tags = $this->github->tags($repository->getGithubUser(), $repository->getGithubRepo());
        $status = (count($tags) > 0) ? 200: 404;

        $tags = array_map(function ($tag) use ($repository) {
            $reference = sprintf('tag/%s', $tag['name']);
            return [
                'name' => $tag['name'],
                'text' => $this->url->formatGitReference($reference),
                'reference' => $reference,
                'commit' => $tag['object']['sha'],
                'url' => $this->url->githubTreeUrl($repository->getGithubUser(), $repository->getGithubRepo(), $tag['name'])
            ];
        }, $tags);

        $this->formatter->respond($this->buildResource(
            [
                'count' => count($tags),
                'tags' => $tags
            ],
            [],
            [
                'repository' => $this->repositoryNormalizer->link($repository)
            ]
        ), $status);
    }
}
