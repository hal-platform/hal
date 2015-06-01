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
    public function link(Group $group = null)
    {
        return  (is_null($group)) ? null : $this->buildLink(
            ['api.group', ['id' => $group->id()]],
            [
                'title' => $group->key()
            ]
        );
    }

    /**
     * @param Group $group
     * @return array
     */
    public function resource(Group $group = null)
    {
        if (is_null($group)) {
            return null;
        }

        return $this->buildResource(
            [
                'id' => $group->id(),
                'key' => $group->key(),
                'name' => $group->name()
            ],
            [],
            [
                'self' => $this->link($group)
            ]
        );
    }
}
