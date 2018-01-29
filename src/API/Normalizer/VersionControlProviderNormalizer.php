<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\API\Normalizer;

use Hal\Core\Entity\System\VersionControlProvider;
use Hal\UI\API\Hyperlink;
use Hal\UI\API\HypermediaResource;
use Hal\UI\API\ResourceNormalizerInterface;

class VersionControlProviderNormalizer implements ResourceNormalizerInterface
{
    /**
     * @param Environment $input
     *
     * @return mixed
     */
    public function normalize($input)
    {
        return $this->resource($input);
    }

    /**
     * @param VersionControlProvider $vcs
     *
     * @return Hyperlink|null
     */
    public function link($vcs): ?Hyperlink
    {
        if (!$vcs instanceof VersionControlProvider) {
            return null;
        }

        return new Hyperlink(
            ['api.vcs_provider', ['system_vcs' => $vcs->id()]],
            $vcs->name()
        );
    }

    /**
     * @param VersionControlProvider|null $vcs
     * @param array $embed
     *
     * @return HypermediaResource|null
     */
    public function resource($vcs, array $embed = []): ?HypermediaResource
    {
        if (!$vcs instanceof VersionControlProvider) {
            return null;
        }

        $data = [
            'id' => $vcs->id(),
            'name' => $vcs->name(),
            'type' => $vcs->type(),
            'parameters' => $vcs->parameters(),
        ];

        $links = [
            'self' => $this->link($vcs)
        ];

        $resource = new HypermediaResource($data, $links);

        $resource->withEmbedded($embed);

        return $resource;
    }
}
