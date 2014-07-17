<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Deployment;

use QL\Hal\Api\PushNormalizer;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Entity\Repository\DeploymentRepository;
use QL\Hal\Helpers\ApiHelper;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * API Last Dush of Deployment Controller
 */
class LastPushController
{
    /**
     * @var ApiHelper
     */
    private $api;

    /**
     * @var DeploymentRepository
     */
    private $deploymentRepo;

    /**
     * @var PushNormalizer
     */
    private $normalizer;

    /**
     * @param ApiHelper $api
     * @param DeploymentRepository $deploymentRepo
     * @param PushNormalizer $normalizer
     */
    public function __construct(
        ApiHelper $api,
        DeploymentRepository $deploymentRepo,
        PushNormalizer $normalizer
    ) {
        $this->api = $api;
        $this->deploymentRepo = $deploymentRepo;
        $this->normalizer = $normalizer;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     */
    public function __invoke(Request $request, Response $response, array $params = [])
    {
        $deployment = $this->deploymentRepo->findOneBy(['id' => $params['id']]);
        if (!$deployment instanceof Deployment) {
            return $response->setStatus(404);
        }

        $status = $request->get('status');
        if ($status && !in_array($status, ['Waiting', 'Pushing', 'Error', 'Success'])) {
            return $response->setStatus(400);
        }

        if ($status === 'Success') {
            $push = $this->deploymentRepo->getLastSuccessfulPush($deployment);
        } else {
            $push = $this->deploymentRepo->getLastPush($deployment);
        }

        if (!$push) {
            return $response->setStatus(404);
        }

        $this->api->prepareResponse($response, $this->normalizer->normalize($push));
    }
}
