<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Api\Normalizer;

use QL\Hal\Api\Hyperlink;
use QL\Hal\Api\NormalizerInterface;
use QL\Hal\Api\Utility\EmbeddedResolutionTrait;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Deployment;

class DeploymentNormalizer implements NormalizerInterface
{
    use HypermediaResourceTrait;
    use EmbeddedResolutionTrait;

    /**
     * @var ApplicationNormalizer
     */
    private $appNormalizer;

    /**
     * @var ServerNormalizer
     */
    private $serverNormalizer;

    /**
     * @var array
     */
    private $embed;

    /**
     * @param ApplicationNormalizer $appNormalizer
     * @param ServerNormalizer $serverNormalizer
     */
    public function __construct(
        ApplicationNormalizer $appNormalizer,
        ServerNormalizer $serverNormalizer
    ) {
        $this->appNormalizer = $appNormalizer;
        $this->serverNormalizer = $serverNormalizer;

        $this->embed = [];
    }

    /**
     * @param Deployment $input
     *
     * @return array
     */
    public function normalize($input)
    {
        return $this->resource($input);
    }

    /**
     * @param Deployment $deployment
     *
     * @return Hyperlink|null
     */
    public function link(Deployment $deployment = null)
    {
        if (!$deployment) {
            return null;
        }

        return new Hyperlink(
            ['api.deployment', ['id' => $deployment->id()]],
            $deployment->formatPretty(true)
        );
    }

    /**
     * @param Deployment $deployment
     * @param array $embed
     *
     * @return array
     */
    public function resource(Deployment $deployment = null, array $embed = [])
    {
        if (is_null($deployment)) {
            return null;
        }

        $properties = [
            'application' => $deployment->application(),
            'server' => $deployment->server()
        ];

        return $this->buildResource(
            [
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

                'url' => $deployment->url(),

                'pretty-name' => $deployment->formatPretty(false),
                'detail' => sprintf('%s: %s', $deployment->server()->formatHumanType(), $deployment->formatMeta()),
            ],
            $this->resolveEmbedded($properties, array_merge($this->embed, $embed)),
            [
                'self' => $this->link($deployment),
                'application' => $this->appNormalizer->link($deployment->application()),
                'server' => $this->serverNormalizer->link($deployment->server()),

                'pushes' => new Hyperlink(['api.deployment.history', ['id' => $deployment->id()]]),
                'last-push' => new Hyperlink(['api.deployment.lastpush', ['id' => $deployment->id()]]),
                'last-successful-push' => new Hyperlink(
                    ['api.deployment.lastpush', ['id' => $deployment->id()], ['status' => 'Success']]
                )
            ]
        );
    }
}
