<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Server;

use QL\Hal\Api\ServerNormalizer;
use QL\Hal\Core\Entity\Repository\ServerRepository;
use QL\Hal\Core\Entity\Server;
use QL\Hal\Helpers\ApiHelper;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * API Server Controller
 */
class ServerController
{
    /**
     * @type ApiHelper
     */
    private $api;

    /**
     * @type ServerRepository
     */
    private $serverRepo;

    /**
     * @type ServerNormalizer
     */
    private $normalizer;

    /**
     * @param ApiHelper $api
     * @param ServerRepository $serverRepo
     * @param ServerNormalizer $normalizer
     */
    public function __construct(
        ApiHelper $api,
        ServerRepository $serverRepo,
        ServerNormalizer $normalizer
    ) {
        $this->api = $api;
        $this->serverRepo = $serverRepo;
        $this->normalizer = $normalizer;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     */
    public function __invoke(Request $request, Response $response, array $params = [])
    {
        $server = $this->serverRepo->findOneBy(['id' => $params['id']]);
        if (!$server instanceof Server) {
            return $response->setStatus(404);
        }

        $this->api->prepareResponse($response, $this->normalizer->normalize($server));
    }
}
