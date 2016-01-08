<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Environment;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Core\Entity\Environment;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Exception\HTTPProblemException;

class EnvironmentController implements ControllerInterface
{
    /**
     * @type ResponseFormatter
     */
    private $formatter;

    /**
     * @type EntityRepository
     */
    private $envRepo;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param ResponseFormatter $formatter
     * @param EntityManagerInterface $em
     * @param array $parameters
     */
    public function __construct(ResponseFormatter $formatter, EntityManagerInterface $em, array $parameters)
    {
        $this->formatter = $formatter;
        $this->envRepo = $em->getRepository(Environment::CLASS);
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     * @throws HTTPProblemException
     */
    public function __invoke()
    {
        $environment = $this->envRepo->find($this->parameters['id']);

        if (!$environment instanceof Environment) {
            throw new HTTPProblemException(404, 'Invalid environment ID specified');
        }

        $this->formatter->respond($environment);
    }
}
