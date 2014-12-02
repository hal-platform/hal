<?php

namespace QL\Hal\Api\Normalizer;

use QL\Hal\Api\EmbeddedResolutionTrait;
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

        $this->embed = ['deployments'];
    }

    /**
     * @param Server $server
     * @return array
     */
    public function link(Server $server)
    {
        return $this->buildLink(
            ['api.server', ['id' => $server->getId()]],
            [
                'title' => $server->getName()
            ]
        );
    }

    /**
     * @param Server $server
     * @param array $embed
     * @return array
     */
    public function resource(Server $server, array $embed = [])
    {
        $properties = [
            'environment' => $server->getEnvironment(),
            'deployments' => $server->getDeployments()
        ];

        return $this->buildResource(
            [
                'id' => $server->getId(),
                'name' => $server->getName()
            ],
            $this->resolveEmbedded($properties, array_merge($this->embed, $embed)),
            [
                'self' => $this->link($server),
                'environment' => $this->environments->link($server->getEnvironment())
            ]
        );
    }
}