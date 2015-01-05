<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Deployment;

use QL\Hal\Core\Entity\Type\PushStatusEnumType;
use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Repository\DeploymentRepository;
use QL\Hal\Core\Entity\Repository\PushRepository;
use QL\HttpProblem\HttpProblemException;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * API Last Dush of Deployment Controller
 */
class LastPushController
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
     * @type PushRepository
     */
    private $pushRepo;

    /**
     * @param ResponseFormatter $formatter
     * @param DeploymentRepository $deploymentRepo
     * @param PushRepository $pushRepo
     */
    public function __construct(
        ResponseFormatter $formatter,
        DeploymentRepository $deploymentRepo,
        PushRepository $pushRepo
    ) {
        $this->formatter = $formatter;
        $this->deploymentRepo = $deploymentRepo;
        $this->pushRepo = $pushRepo;
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

        $status = $request->get('status');

        if ($status && !in_array($status, PushStatusEnumType::values())) {
            throw HttpProblemException::build(400, 'invalid-status');
        }

        if ($status === 'Success') {
            $push = $this->pushRepo->getMostRecentSuccessByDeployment($deployment);
        } else {
            $push = $this->pushRepo->getMostRecentByDeployment($deployment);
        }

        if (!$push) {
            throw HttpProblemException::build(404, 'no-pushes');
        }

        $this->formatter->respond($push);
    }
}
