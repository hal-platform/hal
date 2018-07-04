<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\API\Normalizer;

use Hal\Core\Parameters;
use Hal\Core\Type\TargetEnum;
use Hal\UI\API\Hyperlink;
use Hal\UI\API\HypermediaResource;
use Hal\UI\API\ResourceNormalizerInterface;

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
            $target->name()
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
            'type' => $target->type(),
            'parameters' => $this->fillParameters($target),
        ];

        $links = [
            'self' => $this->link($target),

            'releases' => new Hyperlink(['api.target.history', ['target' => $target->id()]]),
            'current_release' => new Hyperlink(['api.target.current_release', ['target' => $target->id()]]),
        ];

        $resource = new HypermediaResource($data, $links, [
            'application' => $target->application(),
            'environment' => $target->environment(),
            'template' => $target->template(),
        ]);

        $resource->withEmbedded($embed);

        return $resource;
    }

    private function fillParameters(Target $target)
    {
        $type = $target->type();

        switch ($type) {
            case TargetEnum::TYPE_S3:
                return [
                    's3_method' => $target->parameter(Parameters::TARGET_S3_METHOD),
                    'bucket' => $target->parameter(Parameters::TARGET_S3_BUCKET),
                    'source' => $target->parameter(Parameters::TARGET_S3_LOCAL_PATH),
                    'path' => $target->parameter(Parameters::TARGET_S3_REMOTE_PATH),
                ];

            case TargetEnum::TYPE_EB:
                return [
                    'bucket' => $target->parameter(Parameters::TARGET_S3_BUCKET),
                    'source' => $target->parameter(Parameters::TARGET_S3_LOCAL_PATH),
                    'path' => $target->parameter(Parameters::TARGET_S3_REMOTE_PATH),
                    'application' => $target->parameter(Parameters::TARGET_EB_APP),
                    'environment' => $target->parameter(Parameters::TARGET_EB_ENV),
                ];

            case TargetEnum::TYPE_CD:
                return [
                    'bucket' => $target->parameter(Parameters::TARGET_S3_BUCKET),
                    'source' => $target->parameter(Parameters::TARGET_S3_LOCAL_PATH),
                    'path' => $target->parameter(Parameters::TARGET_S3_REMOTE_PATH),
                    'application' => $target->parameter(Parameters::TARGET_CD_APP),
                    'group' => $target->parameter(Parameters::TARGET_CD_GROUP),
                    'configuration' => $target->parameter(Parameters::TARGET_CD_CONFIG),
                ];

            case TargetEnum::TYPE_RSYNC:
                return ['path' => $target->parameter(Parameters::TARGET_RSYNC_REMOTE_PATH)];

            case TargetEnum::TYPE_SCRIPT:
                return ['context' => $target->parameter(Parameters::TARGET_CONTEXT)];

            default:
                return [];
        }
    }
}
