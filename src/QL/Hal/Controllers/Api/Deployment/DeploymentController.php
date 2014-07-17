<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Deployment;

use QL\Hal\Api\DeploymentNormalizer;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Repository\DeploymentRepository;
use QL\Hal\Helpers\ApiHelper;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * API Deployment Controller
 */
class DeploymentController
{
    /**
     * @var ApiHelper
     */
    private $api;

    /**
     * @var DeploymentRepository
     */
    private $deploymentRepo;

    /**
     * @var DeploymentNormalizer
     */
    private $normalizer;

    /**
     * @param ApiHelper $api
     * @param DeploymentRepository $deploymentRepo
     * @param DeploymentNormalizer $normalizer
     */
    public function __construct(
        ApiHelper $api,
        DeploymentRepository $deploymentRepo,
        DeploymentNormalizer $normalizer
    ) {
        $this->api = $api;
        $this->deploymentRepo = $deploymentRepo;
        $this->normalizer = $normalizer;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     */
    public function __invoke(Request $request, Response $response, array $params = [])
    {
        $deployment = $this->deploymentRepo->findOneBy(['id' => $params['id']]);
        if (!$deployment instanceof Deployment) {
            return $response->setStatus(404);
        }

        $this->api->prepareResponse($response, $this->normalizer->normalize($deployment));
    }
}
