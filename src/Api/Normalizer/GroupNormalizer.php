<?php

namespace QL\Hal\Api\Normalizer;

use QL\Hal\Api\Utility\HypermediaLinkTrait;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Group;

/**
 *
 */
class GroupNormalizer
{
    use HypermediaLinkTrait;
    use HypermediaResourceTrait;

    /**
     * @param Group $group
     * @return array
     */
    public function link(Group $group)
    {
        return $this->buildLink(
            ['api.group', ['id' => $group->getId()]],
            [
                'title' => $group->getKey()
            ]
        );
    }

    /**
     * @param Group $group
     * @return array
     */
    public function resource(Group $group)
    {
        return $this->buildResource(
            [
                'id' => $group->getId(),
                'key' => $group->getKey(),
                'name' => $group->getName()
            ],
            [],
            [
                'self' => $this->link($group)
            ]
        );
    }
}