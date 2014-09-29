<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Build;

use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Repository\BuildRepository;
use QL\Hal\Core\Entity\User;
use QL\Hal\Helpers\ApiHelper;
use QL\Hal\Api\BuildNormalizer;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * API Build Controller
 */
class BuildController
{
    /**
     * @type ApiHelper
     */
    private $api;

    /**
     * @type BuildRepository
     */
    private $buildRepo;

    /**
     * @type BuildNormalizer
     */
    private $normalizer;

    /**
     * @param ApiHelper $api
     * @param BuildRepository $buildRepo
     * @param BuildNormalizer $normalizer
     */
    public function __construct(
        ApiHelper $api,
        BuildRepository $buildRepo,
        BuildNormalizer $normalizer
    ) {
        $this->api = $api;
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
        $build = $this->buildRepo->findOneBy(['id' => $params['id']]);

        if (!$build instanceof Build) {
            return $response->setStatus(404);
        }

        $this->api->prepareResponse($response, $this->normalizer->normalize($build));
    }
}
