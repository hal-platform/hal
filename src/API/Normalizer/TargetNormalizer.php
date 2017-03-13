<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\API\Normalizer;

use Hal\UI\API\Hyperlink;
use Hal\UI\API\HypermediaResource;
use Hal\UI\API\NormalizerInterface;
use QL\Hal\Core\Entity\Deployment;

class TargetNormalizer implements NormalizerInterface
{
    /**
     * @param Deployment $input
     *
     * @return mixed
     */
    public function normalize($input)
    {
        return $this->resource($input);
    }

    /**
     * @param Deployment|null $deployment
     *
     * @return Hyperlink|null
     */
    public function link($deployment): ?Hyperlink
    {
        if (!$deployment instanceof Deployment) {
            return null;
        }

        return new Hyperlink(
            ['api.target', ['target' => $deployment->id()]],
            $deployment->formatPretty(true)
        );
    }

    /**
     * @param Deployment|null $deployment
     * @param array $embed
     *
     * @return HypermediaResource|null
     */
    public function resource($deployment, array $embed = []): ?HypermediaResource
    {
        if (!$deployment instanceof Deployment) {
            return null;
        }

        $data = [
            'id' => $deployment->id(),
            'name' => $deployment->name(),

            'path' => $deployment->path(),

            'cd-name' => $deployment->cdName(),
            'cd-group' => $deployment->cdGroup(),
            'cd-configuration' => $deployment->cdConfiguration(),

            'eb-name' => $deployment->ebName(),
            'eb-environment' => $deployment->ebEnvironment(),

            's3-bucket' => $deployment->s3bucket(),
            's3-file' => $deployment->s3file(),

            'script-context' => $deployment->scriptContext(),

            'url' => $deployment->url(),

            'pretty-name' => $deployment->formatPretty(false),
            'detail' => sprintf('%s: %s', $deployment->server()->formatHumanType(), $deployment->formatMeta()),
        ];

        $links = [
            'self' => $this->link($deployment),

            'pushes' => new Hyperlink(['api.target.history', ['target' => $deployment->id()]]),
            'current_release' => new Hyperlink(['api.target.current_release', ['target' => $deployment->id()]])
        ];

        $resource = new HypermediaResource($data, $links, [
            'application' => $deployment->application(),
            'server' => $deployment->server()
        ]);

        $resource->withEmbedded($embed);

        return $resource;
    }
}
