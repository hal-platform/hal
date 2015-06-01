<?php

namespace QL\Hal\Api\Normalizer;

use QL\Hal\Api\Utility\EmbeddedResolutionTrait;
use QL\Hal\Api\Utility\HypermediaLinkTrait;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Server;

/**
 * Server Object Normalizer
 */
class ServerNormalizer
{
    use HypermediaLinkTrait;
    use HypermediaResourceTrait;
    use EmbeddedResolutionTrait;

    /**
     * @var EnvironmentNormalizer
     */
    private $environments;

    /**
     * @var array
     */
    private $embed;

    /**
     * @param EnvironmentNormalizer $environments
     */
    public function __construct(
        EnvironmentNormalizer $environments
    ) {
        $this->environments = $environments;

        $this->embed = ['environment'];
    }

    /**
     * @param Server $server
     * @return array
     */
    public function link(Server $server = null)
    {
        return (is_null($server)) ? null :$this->buildLink(
            ['api.server', ['id' => $server->id()]],
            [
                'title' => $server->name()
            ]
        );
    }

    /**
     * @param Server $server
     * @param array $embed
     * @return array
     */
    public function resource(Server $server = null, array $embed = [])
    {
        if (is_null($server)) {
            return null;
        }

        $properties = [
            'environment' => $server->environment(),
            'deployments' => $server->deployments()->toArray()
        ];

        // shitty, but this is a circular reference so deal with it
        $deployments = [];
        foreach ($server->deployments() as $deployment) {
            $deployments[] = $this->buildLink(
                ['api.deployment', ['id' => $deployment->id()]],
                ['title' => $server->name()]
            );
        }

        return $this->buildResource(
            [
                'id' => $server->id(),
                'type' => $server->type(),
                'name' => $server->name()
            ],
            $this->resolveEmbedded($properties, array_merge($this->embed, $embed)),
            [
                'self' => $this->link($server),
                'environment' => $this->environments->link($server->environment()),
                'deployments' => $deployments
            ]
        );
    }
}
