<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\API\Normalizer;

use Hal\Core\Entity\Group;
use Hal\Core\Type\GroupEnum;
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
            'parameters' => $this->fillParameters($target)
        ];

        $links = [
            'self' => $this->link($target),

            'releases' => new Hyperlink(['api.target.history', ['target' => $target->id()]]),
            'current_release' => new Hyperlink(['api.target.current_release', ['target' => $target->id()]])
        ];

        $resource = new HypermediaResource($data, $links, [
            'application' => $target->application(),
            'environment' => $target->environment(),
            'template' => $target->template()
        ]);

        $resource->withEmbedded($embed);

        return $resource;
    }

    private function fillParameters(Target $target)
    {
        $type = $target->type();

        switch ($type) {
            case GroupEnum::TYPE_S3:
                return [
                    's3_method' => $target->parameter(Target::PARAM_S3_METHOD),
                    's3_bucket' => $target->parameter(Target::PARAM_BUCKET),
                    's3_local_path' => $target->parameter(Target::PARAM_LOCAL_PATH),
                    's3_remote_path' => $target->parameter(Target::PARAM_REMOTE_PATH),
                ];

            case GroupEnum::TYPE_EB:
                return [
                    's3_bucket' => $target->parameter(Target::PARAM_BUCKET),
                    's3_local_path' => $target->parameter(Target::PARAM_LOCAL_PATH),
                    's3_remote_path' => $target->parameter(Target::PARAM_REMOTE_PATH),
                    'eb_name' => $target->parameter(Target::PARAM_APP),
                    'eb_environment' => $target->parameter(Target::PARAM_ENV),
                ];

            case GroupEnum::TYPE_CD:
                return [
                    's3_bucket' => $target->parameter(Target::PARAM_BUCKET),
                    's3_local_path' => $target->parameter(Target::PARAM_LOCAL_PATH),
                    's3_remote_path' => $target->parameter(Target::PARAM_REMOTE_PATH),
                    'cd_name' => $target->parameter(Target::PARAM_APP),
                    'cd_group' => $target->parameter(Target::PARAM_GROUP),
                    'cd_configuration' => $target->parameter(Target::PARAM_CONFIG),
                ];

            case GroupEnum::TYPE_RSYNC:
                return ['path' => $target->parameter(Target::PARAM_REMOTE_PATH)];

            case GroupEnum::TYPE_SCRIPT:
                return ['script_context' => $target->parameter(TARGET::PARAM_CONTEXT)];

            default:
                return [];
        }
    }
}
