<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Group;

use QL\Hal\Api\GroupNormalizer;
use QL\Hal\Core\Entity\Repository\GroupRepository;
use QL\Hal\Helpers\ApiHelper;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * API Groups Controller
 */
class GroupsController
{
    /**
     * @type ApiHelper
     */
    private $api;

    /**
     * @type GroupRepository
     */
    private $groupRepo;

    /**
     * @type GroupNormalizer
     */
    private $normalizer;

    /**
     * @param ApiHelper $api
     * @param GroupRepository $groupRepo
     * @param GroupNormalizer $normalizer
     */
    public function __construct(
        ApiHelper $api,
        GroupRepository $groupRepo,
        GroupNormalizer $normalizer
    ) {
        $this->api = $api;
        $this->groupRepo = $groupRepo;
        $this->normalizer = $normalizer;
    }

    /**
     * @param Request $request
     * @param Response $response
     */
    public function __invoke(Request $request, Response $response)
    {
        $groups = $this->groupRepo->findBy([], ['id' => 'ASC']);
        if (!$groups) {
            return $response->setStatus(404);
        }

        // Normalize all the builds
        $normalized = array_map(function($group) {
            return $this->normalizer->normalize($group);
        }, $groups);

        $this->api->prepareResponse($response, $normalized);
    }
}
