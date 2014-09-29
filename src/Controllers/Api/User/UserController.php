<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\User;

use QL\Hal\Api\UserNormalizer;
use QL\Hal\Core\Entity\Repository\UserRepository;
use QL\Hal\Core\Entity\User;
use QL\Hal\Helpers\ApiHelper;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * API User Controller
 */
class UserController
{
    /**
     * @type ApiHelper
     */
    private $api;

    /**
     * @type UserRepository
     */
    private $userRepo;

    /**
     * @type UserNormalizer
     */
    private $normalizer;

    /**
     * @param ApiHelper $api
     * @param UserRepository $userRepo
     * @param UserNormalizer $normalizer
     */
    public function __construct(
        ApiHelper $api,
        UserRepository $userRepo,
        UserNormalizer $normalizer
    ) {
        $this->api = $api;
        $this->userRepo = $userRepo;
        $this->normalizer = $normalizer;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     */
    public function __invoke(Request $request, Response $response, array $params = [])
    {
        $user = $this->userRepo->findOneBy(['id' => $params['id']]);
        if (!$user instanceof User) {
            return $response->setStatus(404);
        }

        $this->api->prepareResponse($response, $this->normalizer->normalize($user));
    }
}
