<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Push;

use QL\Hal\Api\PushNormalizer;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Entity\Repository;
use QL\Hal\Core\Entity\Repository\PushRepository;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Hal\Helpers\ApiHelper;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * API Pushes Controller
 */
class PushesController
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
     * @type PushRepository
     */
    private $pushRepo;

    /**
     * @type PushNormalizer
     */
    private $normalizer;

    /**
     * @param ApiHelper $api
     * @param RepositoryRepository $repositoryRepo
     * @param PushRepository $pushRepo
     * @param PushNormalizer $normalizer
     */
    public function __construct(
        ApiHelper $api,
        RepositoryRepository $repositoryRepo,
        PushRepository $pushRepo,
        PushNormalizer $normalizer
    ) {
        $this->api = $api;
        $this->repositoryRepo = $repositoryRepo;
        $this->pushRepo = $pushRepo;
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

        $pushes = $this->pushRepo->getForRepository($repository);
        if (!$pushes) {
            return $response->setStatus(404);
        }

        // using this to play with the idea of linked vs embedded resources
        $isResolved = false;

        $content = [
            'count' => count($pushes),
            '_links' => [
                'self' => $this->api->parseLink(['href' => ['api.pushes', ['id' => $repository->getId()]]])
            ]
        ];

        $content = array_merge_recursive($content, $this->normalizePushes($pushes, $isResolved));

        $this->api->prepareResponse($response, $content);
    }

    /**
     * @param array $pushes
     * @param boolean $isResolved
     * @return array
     */
    private function normalizePushes(array $pushes, $isResolved)
    {
        $normalized = array_map(function($push) use ($isResolved) {
            if ($isResolved) {
                return $this->normalizer->normalize($push);
            }

            return $this->normalizer->linked($push);
        }, $pushes);


        $type = ($isResolved) ? '_embedded' : '_links';
        return [
            $type => [
                'pushes' => $normalized
            ]
        ];
    }
}
