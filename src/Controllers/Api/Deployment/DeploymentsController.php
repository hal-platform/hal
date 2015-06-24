<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Deployment;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Api\Normalizer\DeploymentNormalizer;
use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Deployment;
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
    private $applicationRepo;
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
     * @param EntityManagerInterface $em
     * @param DeploymentNormalizer $normalizer
     * @param array $parameters
     */
    public function __construct(
        ResponseFormatter $formatter,
        EntityManagerInterface $em,
        DeploymentNormalizer $normalizer,
        array $parameters
    ) {
        $this->formatter = $formatter;

        $this->applicationRepo = $em->getRepository(Application::CLASS);
        $this->deploymentRepo = $em->getRepository(Deployment::CLASS);

        $this->normalizer = $normalizer;

        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $application = $this->applicationRepo->find($this->parameters['id']);

        if (!$application instanceof Application) {
            throw HttpProblemException::build(404, 'invalid-application');
        }

        $deployments = $this->deploymentRepo->findBy(['application' => $application], ['id' => 'ASC']);
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
