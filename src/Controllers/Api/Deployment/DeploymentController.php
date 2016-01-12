<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
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
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @var EntityRepository
     */
    private $deploymentRepo;

    /**
     * @var DeploymentNormalizer
     */
    private $normalizer;

    /**
     * @var array
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
