<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Group;

use QL\Hal\Api\Normalizer\GroupNormalizer;
use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Repository\GroupRepository;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * API Groups Controller
 */
class GroupsController
{
    use HypermediaResourceTrait;

    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @type GroupRepository
     */
    private $groupRepo;

    /**
     * @type GroupNormalizer
     */
    private $normalizer;

    /**
     * @param ResponseFormatter $formatter
     * @param GroupRepository $groupRepo
     * @param GroupNormalizer $normalizer
     */
    public function __construct(
        ResponseFormatter $formatter,
        GroupRepository $groupRepo,
        GroupNormalizer $normalizer
    ) {
        $this->formatter = $formatter;
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
        $status = (count($groups) > 0) ? 200 : 404;

        $groups = array_map(function ($group) {
            return $this->normalizer->link($group);
        }, $groups);

        $this->formatter->respond($this->buildResource(
            [
                'count' => count($groups)
            ],
            [],
            [
                'groups' => $groups
            ]
        ), $status);
    }
}
