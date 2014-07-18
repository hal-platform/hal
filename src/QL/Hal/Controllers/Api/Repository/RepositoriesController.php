<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Repository;

use QL\Hal\Api\RepositoryNormalizer;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Hal\Helpers\ApiHelper;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * API Repositories Controller
 */
class RepositoriesController
{
    /**
     * @type ApiHelper
     */
    private $api;

    /**
     * @type RepositoryRepository
     */
    private $repositoryRepo;

    /**
     * @type RepositoryNormalizer
     */
    private $normalizer;

    /**
     * @param ApiHelper $api
     * @param RepositoryRepository $repositoryRepo
     * @param RepositoryNormalizer $normalizer
     */
    public function __construct(
        ApiHelper $api,
        RepositoryRepository $repositoryRepo,
        RepositoryNormalizer $normalizer
    ) {
        $this->api = $api;
        $this->repositoryRepo = $repositoryRepo;
        $this->normalizer = $normalizer;
    }

    /**
     * @param Request $request
     * @param Response $response
     */
    public function __invoke(Request $request, Response $response)
    {
        $repos = $this->repositoryRepo->findBy([], ['id' => 'ASC']);
        if (!$repos) {
            return $response->setStatus(404);
        }

        // using this to play with the idea of linked vs embedded resources
        $isResolved = false;

        $content = [
            'count' => count($repos),
            '_links' => [
                'self' => $this->api->parseLink(['href' => 'api.repositories'])
            ]
        ];

        $content = array_merge_recursive($content, $this->normalizeRepositories($repos, $isResolved));

        $this->api->prepareResponse($response, $content);
    }

    /**
     * @param array $repos
     * @param boolean $isResolved
     * @return array
     */
    private function normalizeRepositories(array $repos, $isResolved)
    {
        // Normalize all the builds
        $normalized = array_map(function($repo) use ($isResolved) {
            if ($isResolved) {
                return $this->normalizer->normalize($repo);
            }

            return $this->normalizer->linked($repo);
        }, $repos);


        $type = ($isResolved) ? '_embedded' : '_links';
        return [
            $type => [
                'repositories' => $normalized
            ]
        ];
    }
}
