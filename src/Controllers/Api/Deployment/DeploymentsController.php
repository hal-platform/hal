<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Deployment;

use QL\Hal\Api\Normalizer\DeploymentNormalizer;
use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Repository;
use QL\Hal\Core\Entity\Repository\DeploymentRepository;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\HttpProblem\HttpProblemException;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * API Deployments Controller
 */
class DeploymentsController
{
    use HypermediaResourceTrait;

    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @type RepositoryRepository
     */
    private $repositoryRepo;

    /**
     * @type DeploymentRepository
     */
    private $deploymentRepo;

    /**
     * @var DeploymentNormalizer
     */
    private $normalizer;

    /**
     * @param ResponseFormatter $formatter
     * @param RepositoryRepository $repositoryRepo
     * @param DeploymentRepository $deploymentRepo
     * @param DeploymentNormalizer $normalizer
     */
    public function __construct(
        ResponseFormatter $formatter,
        RepositoryRepository $repositoryRepo,
        DeploymentRepository $deploymentRepo,
        DeploymentNormalizer $normalizer
    ) {
        $this->formatter = $formatter;
        $this->repositoryRepo = $repositoryRepo;
        $this->deploymentRepo = $deploymentRepo;
        $this->normalizer = $normalizer;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @throws HttpProblemException
     */
    public function __invoke(Request $request, Response $response, array $params = [])
    {
        $repository = $this->repositoryRepo->findOneBy(['id' => $params['id']]);

        if (!$repository instanceof Repository) {
            throw HttpProblemException::build(404, 'invalid-repository');
        }

        $deployments = $this->deploymentRepo->findBy(['repository' => $repository], ['id' => 'ASC']);
        $status = (count($deployments) > 0) ? 200 : 404;

        $deployments = array_map(function ($deployment) {
            return $this->normalizer->link($deployment);
        }, $deployments);

        $this->formatter->respond($this->buildResource(
            [
                'count' => count($deployments)
            ],
            [],
            [
                'deployments' => $deployments
            ]
        ), $status);
    }
}
