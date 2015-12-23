<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Api\Normalizer;

use QL\Hal\Api\Hyperlink;
use QL\Hal\Api\NormalizerInterface;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Group;

class GroupNormalizer implements NormalizerInterface
{
    use HypermediaResourceTrait;

    /**
     * @param Group $input
     *
     * @return array
     */
    public function normalize($input)
    {
        return $this->resource($input);
    }

    /**
     * @param Group $group
     *
     * @return Hyperlink|null
     */
    public function link(Group $group = null)
    {
        if (!$group) {
            return null;
        }

        return new Hyperlink(
            ['api.group', ['id' => $group->id()]],
            $group->key()
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
