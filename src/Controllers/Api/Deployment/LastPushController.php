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
use QL\Panthor\ControllerInterface;
use Slim\Http\Request;

class LastPushController implements ControllerInterface
{
    const FILTER_STATUS = 'status';

    /**
     * @type Request
     */
    private $request;

    /**
     * @type ResponseFormatter
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
     * @type array
     */
    private $parameters;

    /**
     * @param Request $request
     * @param ResponseFormatter $formatter
     * @param DeploymentRepository $deploymentRepo
     * @param PushRepository $pushRepo
     * @param array $parameters
     */
    public function __construct(
        Request $request,
        ResponseFormatter $formatter,
        DeploymentRepository $deploymentRepo,
        PushRepository $pushRepo,
        array $parameters
    ) {
        $this->request = $request;
        $this->formatter = $formatter;
        $this->deploymentRepo = $deploymentRepo;
        $this->pushRepo = $pushRepo;

        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     * @throws HttpProblemException
     */
    public function __invoke()
    {
        $deployment = $this->deploymentRepo->find($this->parameters['id']);

        if (!$deployment instanceof Deployment) {
            throw HttpProblemException::build(404, 'invalid-deployment');
        }

        $status = $this->request->get(self::FILTER_STATUS);

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
