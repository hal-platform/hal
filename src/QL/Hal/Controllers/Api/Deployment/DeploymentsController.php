<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Deployment;

use QL\Hal\Api\DeploymentNormalizer;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Repository;
use QL\Hal\Core\Entity\Repository\DeploymentRepository;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Hal\Helpers\ApiHelper;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * API Deployments Controller
 */
class DeploymentsController
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
     * @type DeploymentRepository
     */
    private $deploymentRepo;

    /**
     * @type DeploymentNormalizer
     */
    private $normalizer;

    /**
     * @param ApiHelper $api
     * @param RepositoryRepository $repositoryRepo
     * @param DeploymentRepository $deploymentRepo
     * @param DeploymentNormalizer $normalizer
     */
    public function __construct(
        ApiHelper $api,
        RepositoryRepository $repositoryRepo,
        DeploymentRepository $deploymentRepo,
        DeploymentNormalizer $normalizer
    ) {
        $this->api = $api;
        $this->repositoryRepo = $repositoryRepo;
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
        $repository = $this->repositoryRepo->findOneBy(['id' => $params['id']]);
        if (!$repository instanceof Repository) {
            return $response->setStatus(404);
        }

        $deployments = $this->deploymentRepo->findBy(['repository' => $repository], ['id' => 'ASC']);
        if (!$deployments) {
            return $response->setStatus(404);
        }

        // Normalize all the deployments
        $normalized = array_map(function($deployment) {
            return $this->normalizer->normalize($deployment);
        }, $deployments);

        $this->api->prepareResponse($response, $normalized);
    }
}
