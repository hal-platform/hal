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
 * API Users Controller
 */
class UsersController
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
     */
    public function __invoke(Request $request, Response $response)
    {
        $users = $this->userRepo->findBy([], ['id' => 'ASC']);
        if (!$users) {
            return $response->setStatus(404);
        }

        // Normalize all the builds
        $normalized = array_map(function($users) {
            return $this->normalizer->normalize($users);
        }, $users);

        $this->api->prepareResponse($response, $normalized);
    }
}
