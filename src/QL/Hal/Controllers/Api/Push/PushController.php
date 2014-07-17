<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Push;

use QL\Hal\Api\PushNormalizer;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Entity\Repository\PushRepository;
use QL\Hal\Helpers\ApiHelper;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * API Push Controller
 */
class PushController
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
     * @type PushNormalizer
     */
    private $normalizer;

    /**
     * @param ApiHelper $api
     * @param PushRepository $pushRepo
     * @param PushNormalizer $normalizer
     */
    public function __construct(
        ApiHelper $api,
        PushRepository $pushRepo,
        PushNormalizer $normalizer
    ) {
        $this->api = $api;
        $this->pushRepo = $pushRepo;
        $this->normalizer = $normalizer;
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

        $this->api->prepareResponse($response, $this->normalizer->normalize($push));
    }
}
