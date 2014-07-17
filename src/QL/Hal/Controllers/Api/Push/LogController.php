<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Push;

use QL\Hal\Helpers\ApiHelper;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Entity\Repository\PushRepository;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * NOT CURRENTLY IMPLEMENTED
 */
class LogController
{
    /**
     * @type ApiHelper
     */
    private $api;

    /**
     * @type PushRepository
     */
    private $pushRepo;

    /**
     * @param ApiHelper $api
     * @param PushRepository $pushRepo
     */
    public function __construct(
        ApiHelper $api,
        PushRepository $pushRepo
    ) {
        $this->api = $api;
        $this->pushRepo = $pushRepo;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     */
    public function __invoke(Request $request, Response $response, array $params = [])
    {
        $push = $this->pushRepo->findOneBy(['id' => $params['id']]);

        if (!$push instanceof Push) {
            return $response->setStatus(404);
        }

        // if (!$push->getLog() instanceof Log) {
        //     return $response->setStatus(404);
        // }

        // $log = $this->normalizer->normalize($push->getLog());
        // $this->response->setBody($log);

        $response->setStatus(404);
    }
}
