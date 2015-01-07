<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Repository;

use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Core\Entity\Repository;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\HttpProblem\HttpProblemException;
use QL\Panthor\ControllerInterface;

class RepositoryController implements ControllerInterface
{
    /**
     * @type ResponseFormatter
     */
    private $formatter;

    /**
     * @type RepositoryRepository
     */
    private $repositoryRepo;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param ResponseFormatter $formatter
     * @param RepositoryRepository $repositoryRepo
     * @param array $parameters
     */
    public function __construct(ResponseFormatter $formatter, RepositoryRepository $repositoryRepo, array $parameters)
    {
        $this->formatter = $formatter;
        $this->repositoryRepo = $repositoryRepo;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     * @throws HttpProblemException
     */
    public function __invoke()
    {
        $repository = $this->repositoryRepo->find($this->parameters['id']);

        if (!$repository instanceof Repository) {
            throw HttpProblemException::build(404, 'invalid-repository');
        }

        $this->formatter->respond($repository);
    }
}
