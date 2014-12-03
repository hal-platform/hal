<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Environment;

use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\Repository\EnvironmentRepository;
use QL\HttpProblem\HttpProblemException;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * API Environment Controller
 */
class EnvironmentController
{
    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @type EnvironmentRepository
     */
    private $envRepo;

    /**
     * @param ResponseFormatter $formatter
     * @param EnvironmentRepository $envRepo
     */
    public function __construct(
        ResponseFormatter $formatter,
        EnvironmentRepository $envRepo
    ) {
        $this->formatter = $formatter;
        $this->envRepo = $envRepo;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @throws HttpProblemException
     */
    public function __invoke(Request $request, Response $response, array $params = [])
    {
        $environment = $this->envRepo->findOneBy(['id' => $params['id']]);

        if (!$environment instanceof Environment) {
            throw HttpProblemException::build(404, 'invalid-environment');
        }

        $this->formatter->respond($environment);
    }
}
