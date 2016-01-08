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
use QL\Hal\Core\Entity\Deployment;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Exception\HTTPProblemException;

class DeploymentController implements ControllerInterface
{
    /**
     * @type ResponseFormatter
     */
    private $formatter;

    /**
     * @type EntityRepository
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
     * @param EntityManagerInterface $em
     * @param DeploymentNormalizer $normalizer
     */
    public function __construct(
        ResponseFormatter $formatter,
        EntityManagerInterface $em,
        DeploymentNormalizer $normalizer,
        array $parameters
    ) {
        $this->formatter = $formatter;

        $this->deploymentRepo = $em->getRepository(Deployment::CLASS);

        $this->normalizer = $normalizer;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     * @throws HTTPProblemException
     */
    public function __invoke()
    {
        $deployment = $this->deploymentRepo->find($this->parameters['id']);

        if (!$deployment instanceof Deployment) {
            throw new HTTPProblemException(404, 'Invalid deployment ID specified');
        }

        $this->formatter->respond($this->normalizer->resource($deployment, ['server']));
    }
}
