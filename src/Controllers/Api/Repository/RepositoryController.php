<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Repository;

use QL\Hal\Api\RepositoryNormalizer;
use QL\Hal\Core\Entity\Repository;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Hal\Helpers\ApiHelper;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * API Repository Controller
 */
class RepositoryController
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
     * @param array $params
     */
    public function __invoke(Request $request, Response $response, array $params = [])
    {
        $repository = $this->repositoryRepo->findOneBy(['id' => $params['id']]);
        if (!$repository instanceof Repository) {
            return $response->setStatus(404);
        }

        $this->api->prepareResponse($response, $this->normalizer->normalize($repository));
    }
}
