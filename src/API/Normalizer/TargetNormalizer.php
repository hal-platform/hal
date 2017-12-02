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
use Hal\Core\Entity\Target;

class TargetNormalizer implements ResourceNormalizerInterface
{
    /**
     * @param Target $input
     *
     * @return mixed
     */
    public function normalize($input)
    {
        return $this->resource($input);
    }

    /**
     * @param Target|null $target
     *
     * @return Hyperlink|null
     */
    public function link($target): ?Hyperlink
    {
        if (!$target instanceof Target) {
            return null;
        }

        return new Hyperlink(
            ['api.target', ['target' => $target->id()]],
            $target->format(true)
        );
    }

    /**
     * @param Target|null $target
     * @param array $embed
     *
     * @return HypermediaResource|null
     */
    public function resource($target, array $embed = []): ?HypermediaResource
    {
        if (!$target instanceof Target) {
            return null;
        }

        $data = [
            'id' => $target->id(),
            'name' => $target->name(),
            'url' => $target->url(),

            'configuration' => [
                'path' => $target->parameter(Target::PARAM_PATH),

                'cd_name' => $target->parameter(TARGET::PARAM_APP),
                'cd_group' => $target->parameter(TARGET::PARAM_GROUP),
                'cd_configuration' => $target->parameter(TARGET::PARAM_CONFIG),

                'eb_name' => $target->parameter(TARGET::PARAM_APP),
                'eb_environment' => $target->parameter(Target::PARAM_ENV),

                's3_bucket' => $target->parameter(Target::PARAM_BUCKET),
                's3_file' => $target->parameter(Target::PARAM_PATH),

                'script_context' => $target->parameter(TARGET::PARAM_CONTEXT)
            ],

            'pretty_name' => $target->format(false),
            'detail' => sprintf('%s: %s', $target->group()->format(), $target->formatParameters()),
        ];

        $links = [
            'self' => $this->link($target),

            'pushes' => new Hyperlink(['api.target.history', ['target' => $target->id()]]),
            'current_release' => new Hyperlink(['api.target.current_release', ['target' => $target->id()]])
        ];

        $resource = new HypermediaResource($data, $links, [
            'application' => $target->application(),
            'group' => $target->group()
        ]);

        $resource->withEmbedded($embed);

        return $resource;
    }
}
