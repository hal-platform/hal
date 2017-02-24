<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\API\Normalizer;

use Hal\UI\API\Hyperlink;
use Hal\UI\API\NormalizerInterface;
use Hal\UI\API\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Group;

class GroupNormalizer implements NormalizerInterface
{
    use HypermediaResourceTrait;

    /**
     * @param Group $input
     *
     * @return array|null
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
    public function link(Group $group = null): ?Hyperlink
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
     *
     * @return array|null
     */
    public function resource(Group $group = null)
    {
        if (is_null($group)) {
            return null;
        }

        $data = [
            'id' => $group->id(),
            'key' => $group->key(),
            'name' => $group->name()
        ];

        $embedded = [];

        $links = ['self' => $this->link($group)];

        return $this->buildResource($data, $embedded, $links);
    }
}
