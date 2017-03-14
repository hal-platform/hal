<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\API\Normalizer;

use Hal\UI\API\Hyperlink;
use Hal\UI\API\HypermediaResource;
use Hal\UI\API\ResourceNormalizerInterface;
use QL\Hal\Core\Entity\Group;

class OrganizationNormalizer implements ResourceNormalizerInterface
{
    /**
     * @param Group $input
     *
     * @return mixed
     */
    public function normalize($input)
    {
        if (!$input instanceof Group) {
            return null;
        }

        return $this->resource($input);
    }

    /**
     * @param Group|null $group
     *
     * @return Hyperlink|null
     */
    public function link($group): ?Hyperlink
    {
        if (!$group instanceof Group) {
            return null;
        }

        return new Hyperlink(
            ['api.organization', ['organization' => $group->id()]],
            $group->key()
        );
    }

    /**
     * @param Group|null $group
     *
     * @return HypermediaResource|null
     */
    public function resource($group, array $embed = []): ?HypermediaResource
    {
        if (!$group instanceof Group) {
            return null;
        }

        $data = [
            'id' => $group->id(),
            'key' => $group->key(),
            'name' => $group->name()
        ];

        $links = [
            'self' => $this->link($group)
        ];

        $resource = new HypermediaResource($data, $links);

        $resource->withEmbedded($embed);

        return $resource;
    }
}
