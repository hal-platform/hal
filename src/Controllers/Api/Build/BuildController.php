<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Build;

use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Repository\BuildRepository;
use QL\HttpProblem\HttpProblemException;
use QL\Panthor\ControllerInterface;

class BuildController implements ControllerInterface
{
    /**
     * @type ResponseFormatter
     */
    private $formatter;

    /**
     * @type BuildRepository
     */
    private $buildRepo;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param ResponseFormatter $formatter
     * @param BuildRepository $buildRepo
     * @param array $parameters
     */
    public function __construct(ResponseFormatter $formatter, BuildRepository $buildRepo, array $parameters)
    {
        $this->formatter = $formatter;
        $this->buildRepo = $buildRepo;

        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     * @throws HttpProblemException
     */
    public function __invoke()
    {
        $build = $this->buildRepo->find($this->parameters['id']);

        if (!$build instanceof Build) {
            throw HttpProblemException::build(404, 'invalid-build');
        }

        $this->formatter->respond($build);
    }
}
