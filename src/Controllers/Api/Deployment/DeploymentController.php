<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Deployment;

use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Repository\DeploymentRepository;
use QL\HttpProblem\HttpProblemException;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * API Deployment Controller
 */
class DeploymentController
{
    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @type DeploymentRepository
     */
    private $deploymentRepo;

    /**
     * @param ResponseFormatter $formatter
     * @param DeploymentRepository $deploymentRepo
     */
    public function __construct(
        ResponseFormatter $formatter,
        DeploymentRepository $deploymentRepo
    ) {
        $this->formatter = $formatter;
        $this->deploymentRepo = $deploymentRepo;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @throws HttpProblemException
     */
    public function __invoke(Request $request, Response $response, array $params = [])
    {
        $deployment = $this->deploymentRepo->findOneBy(['id' => $params['id']]);

        if (!$deployment instanceof Deployment) {
            throw HttpProblemException::build(404, 'invalid-deployment');
        }

        $this->formatter->respond($deployment);
    }
}
