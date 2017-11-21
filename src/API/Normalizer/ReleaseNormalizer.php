<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\API\Normalizer;

use Hal\Core\Entity\Release;
use Hal\UI\API\Hyperlink;
use Hal\UI\API\HypermediaResource;
use Hal\UI\API\ResourceNormalizerInterface;

class ReleaseNormalizer implements ResourceNormalizerInterface
{
    /**
     * @var EnvironmentNormalizer
     */
    private $environmentNormalizer;

    /**
     * @param EnvironmentNormalizer $environmentNormalizer
     */
    public function __construct(EnvironmentNormalizer $environmentNormalizer)
    {
        $this->environmentNormalizer = $environmentNormalizer;
    }

    /**
     * @param Release|null $input
     *
     * @return mixed
     */
    public function normalize($input)
    {
        return $this->resource($input);
    }

    /**
     * @param Release|null $release
     *
     * @return Hyperlink|null
     */
    public function link($release): ?Hyperlink
    {
        if (!$release instanceof Release) {
            return null;
        }

        return new Hyperlink(
            ['api.release', ['release' => $release->id()]],
            $release->id()
        );
    }

    /**
     * @param Release|null $release
     * @param array $embed
     *
     * @return HypermediaResource|null
     */
    public function resource($release, array $embed = []): ?HypermediaResource
    {
        if (!$release instanceof Release) {
            return null;
        }

        $data = [
            'id' => $release->id(),
            'status' => $release->status(),

            'created' => $release->created(),
            'start' => $release->start(),
            'end' => $release->end()
        ];

        $links = [
            'self' => $this->link($release),
            'events' => new Hyperlink(['api.release.events', ['release' => $release->id()]]),
            'page' => new Hyperlink(
                ['api.release', ['release' => $release->id()]],
                '',
                'text/html'
            )
        ];

        if ($release->target()) {
            $embed += [
                'environment' => $release->target()->group()->environment()
            ];

            $links += [
                'environment' => $this->environmentNormalizer($release->target()->group()->environment())
            ];
        }

        $resource = new HypermediaResource($data, $links, [
            'user' => $release->user(),
            'build' => $release->build(),
            'target' => $release->target(),
            'application' => $release->application()
        ]);

        $resource->withEmbedded($embed);

        return $resource;
    }
}
