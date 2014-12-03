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
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * API Repository Controller
 */
class RepositoryController
{
    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @type RepositoryRepository
     */
    private $repositoryRepo;

    /**
     * @param ResponseFormatter $formatter
     * @param RepositoryRepository $repositoryRepo
     */
    public function __construct(
        ResponseFormatter $formatter,
        RepositoryRepository $repositoryRepo
    ) {
        $this->formatter = $formatter;
        $this->repositoryRepo = $repositoryRepo;
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

        $this->formatter->respond($repository);
    }
}
