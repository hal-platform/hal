<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Deployment;

use Doctrine\ORM\EntityRepository;
use QL\Hal\Api\Normalizer\DeploymentNormalizer;
use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Repository;
use QL\Hal\Core\Repository\DeploymentRepository;
use QL\HttpProblem\HttpProblemException;
use QL\Panthor\ControllerInterface;

class DeploymentsController implements ControllerInterface
{
    use HypermediaResourceTrait;

    /**
     * @type ResponseFormatter
     */
    private $formatter;

    /**
     * @type EntityRepository
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
     * @type array
     */
    private $parameters;

    /**
     * @param ResponseFormatter $formatter
     * @param EntityRepository $repositoryRepo
     * @param DeploymentRepository $deploymentRepo
     * @param DeploymentNormalizer $normalizer
     * @param array $parameters
     */
    public function __construct(
        ResponseFormatter $formatter,
        EntityRepository $repositoryRepo,
        DeploymentRepository $deploymentRepo,
        DeploymentNormalizer $normalizer,
        array $parameters
    ) {
        $this->formatter = $formatter;
        $this->repositoryRepo = $repositoryRepo;
        $this->deploymentRepo = $deploymentRepo;
        $this->normalizer = $normalizer;

        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $repository = $this->repositoryRepo->find($this->parameters['id']);

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
