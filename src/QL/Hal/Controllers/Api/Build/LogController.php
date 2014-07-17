<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Build;

use QL\Hal\Helpers\ApiHelper;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Log;
use QL\Hal\Core\Entity\Repository\BuildRepository;
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
     * @type BuildRepository
     */
    private $buildRepo;

    /**
     * @param ApiHelper $api
     * @param BuildRepository $buildRepo
     */
    public function __construct(
        ApiHelper $api,
        BuildRepository $buildRepo
    ) {
        $this->api = $api;
        $this->buildRepo = $buildRepo;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     */
    public function __invoke(Request $request, Response $response, array $params = [])
    {
        $build = $this->buildRepo->findOneBy(['id' => $params['id']]);

        if (!$build instanceof Build) {
            return $response->setStatus(404);
        }

        // if (!$build->getLog() instanceof Log) {
        //     return $response->setStatus(404);
        // }

        // $log = $this->normalizer->normalize($build->getLog());
        // $this->response->setBody($log);

        $response->setStatus(404);
    }
}
