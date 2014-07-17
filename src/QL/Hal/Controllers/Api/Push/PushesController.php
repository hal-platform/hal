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

        // Normalize all the builds
        $normalized = array_map(function($push) {
            return $this->normalizer->normalizeLinked($push);
        }, $pushes);

        $this->api->prepareResponse($response, $normalized);
    }
}
