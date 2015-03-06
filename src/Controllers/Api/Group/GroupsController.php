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
use QL\Hal\Core\Repository\GroupRepository;
use QL\Panthor\ControllerInterface;

class GroupsController implements ControllerInterface
{
    use HypermediaResourceTrait;

    /**
     * @type ResponseFormatter
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
     * {@inheritdoc}
     */
    public function __invoke()
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
