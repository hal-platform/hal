<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Environment;

use QL\Hal\Api\EnvironmentNormalizer;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\Repository\EnvironmentRepository;
use QL\Hal\Helpers\ApiHelper;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * API Environment Controller
 */
class EnvironmentController
{
    /**
     * @type ApiHelper
     */
    private $api;

    /**
     * @type EnvironmentRepository
     */
    private $envRepo;

    /**
     * @type EnvironmentNormalizer
     */
    private $normalizer;

    /**
     * @param ApiHelper $api
     * @param EnvironmentRepository $envRepo
     * @param EnvironmentNormalizer $normalizer
     */
    public function __construct(
        ApiHelper $api,
        EnvironmentRepository $envRepo,
        EnvironmentNormalizer $normalizer
    ) {
        $this->api = $api;
        $this->envRepo = $envRepo;
        $this->normalizer = $normalizer;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     */
    public function __invoke(Request $request, Response $response, array $params = [])
    {
        $environment = $this->envRepo->findOneBy(['id' => $params['id']]);
        if (!$environment instanceof Environment) {
            return $response->setStatus(404);
        }

        $this->api->prepareResponse($response, $this->normalizer->normalize($environment));
    }
}
