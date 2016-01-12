<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Controllers\Api\Group;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Api\Normalizer\GroupNormalizer;
use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Group;
use QL\Panthor\ControllerInterface;

class GroupsController implements ControllerInterface
{
    use HypermediaResourceTrait;

    /**
     * @type ResponseFormatter
     */
    private $formatter;

    /**
     * @type EntityRepository
     */
    private $groupRepo;

    /**
     * @type GroupNormalizer
     */
    private $normalizer;

    /**
     * @param ResponseFormatter $formatter
     * @param EntityManagerInterface $em
     * @param GroupNormalizer $normalizer
     */
    public function __construct(
        ResponseFormatter $formatter,
        EntityManagerInterface $em,
        GroupNormalizer $normalizer
    ) {
        $this->formatter = $formatter;
        $this->groupRepo = $em->getRepository(Group::CLASS);
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
