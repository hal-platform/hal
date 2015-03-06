<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Environment;

use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Repository\EnvironmentRepository;
use QL\HttpProblem\HttpProblemException;
use QL\Panthor\ControllerInterface;

class EnvironmentController implements ControllerInterface
{
    /**
     * @type ResponseFormatter
     */
    private $formatter;

    /**
     * @type EnvironmentRepository
     */
    private $envRepo;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param ResponseFormatter $formatter
     * @param EnvironmentRepository $envRepo
     * @param array $parameters
     */
    public function __construct(ResponseFormatter $formatter, EnvironmentRepository $envRepo, array $parameters)
    {
        $this->formatter = $formatter;
        $this->envRepo = $envRepo;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     * @throws HttpProblemException
     */
    public function __invoke()
    {
        $environment = $this->envRepo->find($this->parameters['id']);

        if (!$environment instanceof Environment) {
            throw HttpProblemException::build(404, 'invalid-environment');
        }

        $this->formatter->respond($environment);
    }
}
