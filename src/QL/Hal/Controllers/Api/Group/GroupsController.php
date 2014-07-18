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

        // using this to play with the idea of linked vs embedded resources
        $isResolved = false;

        $content = [
            'count' => count($groups),
            '_links' => [
                'self' => $this->api->parseLink(['href' => 'api.groups'])
            ]
        ];

        $content = array_merge_recursive($content, $this->normalizeGroups($groups, $isResolved));

        $this->api->prepareResponse($response, $content);
    }

    /**
     * @param array $groups
     * @param boolean $isResolved
     * @return array
     */
    private function normalizeGroups(array $groups, $isResolved)
    {
        $normalized = array_map(function($group) use ($isResolved) {
            if ($isResolved) {
                return $this->normalizer->normalize($group);
            }

            return $this->normalizer->linked($group);
        }, $groups);


        $type = ($isResolved) ? '_embedded' : '_links';
        return [
            $type => [
                'groups' => $normalized
            ]
        ];
    }
}
