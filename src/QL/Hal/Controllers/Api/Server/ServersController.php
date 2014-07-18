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
 * API Servers Controller
 */
class ServersController
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
     */
    public function __invoke(Request $request, Response $response)
    {
        $servers = $this->serverRepo->findBy([], ['id' => 'ASC']);
        if (!$servers) {
            return $response->setStatus(404);
        }

        // Normalize all the builds
        $normalized = array_map(function($server) {
            return $this->normalizer->normalize($server);
        }, $servers);

        $this->api->prepareResponse($response, $normalized);
    }
}
