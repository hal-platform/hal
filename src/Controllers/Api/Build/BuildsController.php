<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Build;

use QL\Hal\Api\BuildNormalizer;
use QL\Hal\Core\Entity\Repository\BuildRepository;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Hal\Core\Entity\Repository;
use QL\Hal\Helpers\ApiHelper;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * API Builds Controller
 */
class BuildsController
{

    private $formatter;

    /**
     * @type RepositoryRepository
     */
    private $repositoryRepo;

    /**
     * @type BuildRepository
     */
    private $buildRepo;


    private $normalizer;

    /**
     * @param ApiHelper $api
     * @param RepositoryRepository $repositoryRepo
     * @param BuildRepository $buildRepo
     * @param BuildNormalizer $normalizer
     */
    public function __construct(
        ApiHelper $api,
        RepositoryRepository $repositoryRepo,
        BuildRepository $buildRepo,
        BuildNormalizer $normalizer
    ) {
        $this->api = $api;
        $this->repositoryRepo = $repositoryRepo;
        $this->buildRepo = $buildRepo;
        $this->normalizer = $normalizer;
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

        $builds = $this->buildRepo->findBy(['repository' => $repository], ['status' => 'ASC', 'start' => 'DESC']);
        if (!$builds) {
            return $response->setStatus(404);
        }

        // using this to play with the idea of linked vs embedded resources
        $isResolved = false;

        $content = [
            'count' => count($builds),
            '_links' => [
                'self' => $this->api->parseLink(['href' => ['api.builds', ['id' => $repository->getId()]]])
            ]
        ];

        $content = array_merge_recursive($content, $this->normalizeBuilds($builds, $isResolved));

        $this->api->prepareResponse($response, $content);
    }

    /**
     * @param array $builds
     * @param boolean $isResolved
     * @return array
     */
    private function normalizeBuilds(array $builds, $isResolved)
    {
        $normalized = array_map(function($build) use ($isResolved) {
            if ($isResolved) {
                return $this->normalizer->normalize($build);
            }

            return $this->normalizer->linked($build);
        }, $builds);


        $type = ($isResolved) ? '_embedded' : '_links';
        return [
            $type => [
                'builds' => $normalized
            ]
        ];
    }
}
