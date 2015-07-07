<?php

namespace QL\Hal\Api\Normalizer;

use QL\Hal\Api\Utility\EmbeddedResolutionTrait;
use QL\Hal\Api\Utility\HypermediaLinkTrait;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Deployment;

/**
 * Deployment Object Normalizer
 */
class DeploymentNormalizer
{
    use HypermediaLinkTrait;
    use HypermediaResourceTrait;
    use EmbeddedResolutionTrait;

    /**
     * @var ApplicationNormalizer
     */
    private $appNormalizer;

    /**
     * @var ServerNormalizer
     */
    private $servers;

    /**
     * @var array
     */
    private $embed;

    /**
     * @param ApplicationNormalizer $appNormalizer
     * @param ServerNormalizer $servers
     */
    public function __construct(
        ApplicationNormalizer $appNormalizer,
        ServerNormalizer $servers
    ) {
        $this->appNormalizer = $appNormalizer;
        $this->servers = $servers;

        $this->embed = [];
    }

    /**
     * @param Deployment $deployment
     * @return array
     */
    public function link(Deployment $deployment = null)
    {
        return (is_null($deployment)) ? null : $this->buildLink(
            ['api.deployment', ['id' => $deployment->id()]],
            [
                'title' => $deployment->server()->name()
            ]
        );
    }

    /**
     * @param Deployment $deployment
     * @param array $embed
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
                'path' => $deployment->path(),
                'eb-environment' => $deployment->ebEnvironment(),
                'ec2-pool' => $deployment->ec2Pool(),
                'url' => $deployment->url(),
            ],
            $this->resolveEmbedded($properties, array_merge($this->embed, $embed)),
            [
                'self' => $this->link($deployment),
                'application' => $this->appNormalizer->link($deployment->application()),
                'server' => $this->servers->link($deployment->server()),
                'last-push' => $this->buildLink(['api.deployment.lastpush', ['id' => $deployment->id()]]),
                'last-successful-push' => $this->buildLink(['api.deployment.lastpush', ['id' => $deployment->id()], ['status' => 'Success']])
            ]
        );
    }
}
