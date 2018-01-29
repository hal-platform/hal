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
use Hal\Core\Entity\Organization;

class OrganizationNormalizer implements ResourceNormalizerInterface
{
    /**
     * @param Organization $input
     *
     * @return mixed
     */
    public function normalize($input)
    {
        if (!$input instanceof Organization) {
            return null;
        }

        return $this->resource($input);
    }

    /**
     * @param Organization|null $organization
     *
     * @return Hyperlink|null
     */
    public function link($organization): ?Hyperlink
    {
        if (!$organization instanceof Organization) {
            return null;
        }

        return new Hyperlink(
            ['api.organization', ['organization' => $organization->id()]],
            $organization->name()
        );
    }

    /**
     * @param Organization|null $organization
     *
     * @return HypermediaResource|null
     */
    public function resource($organization, array $embed = []): ?HypermediaResource
    {
        if (!$organization instanceof Organization) {
            return null;
        }

        $data = [
            'id' => $organization->id(),
            'name' => $organization->name()
        ];

        $links = [
            'self' => $this->link($organization)
        ];

        $resource = new HypermediaResource($data, $links);

        $resource->withEmbedded($embed);

        return $resource;
    }
}
