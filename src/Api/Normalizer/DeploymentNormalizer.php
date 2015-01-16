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
     * @var RepositoryNormalizer
     */
    private $repositories;

    /**
     * @var ServerNormalizer
     */
    private $servers;

    /**
     * @var array
     */
    private $embed;

    /**
     * @param RepositoryNormalizer $repositories
     * @param ServerNormalizer $servers
     */
    public function __construct(
        RepositoryNormalizer $repositories,
        ServerNormalizer $servers
    ) {
        $this->repositories = $repositories;
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
            ['api.deployment', ['id' => $deployment->getId()]],
            [
                'title' => $deployment->getServer()->getName()
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
            'repository' => $deployment->getRepository(),
            'server' => $deployment->getServer()
        ];

        return $this->buildResource(
            [
                'id' => $deployment->getId(),
                'path' => $deployment->getPath(),
                'eb-environment' => $deployment->getEbEnvironment(),
                'ec2-pool' => $deployment->getEc2Pool(),
                'url' => $deployment->getUrl(),
            ],
            $this->resolveEmbedded($properties, array_merge($this->embed, $embed)),
            [
                'self' => $this->link($deployment),
                'repository' => $this->repositories->link($deployment->getRepository()),
                'server' => $this->servers->link($deployment->getServer()),
                'last-push' => $this->buildLink(['api.deployment.lastpush', ['id' => $deployment->getId()]]),
                'last-successful-push' => $this->buildLink(['api.deployment.lastpush', ['id' => $deployment->getId()], ['status' => 'Success']])
            ]
        );
    }
}
