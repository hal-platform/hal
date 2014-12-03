<?php

namespace QL\Hal\Api\Normalizer;

use QL\Hal\Api\Utility\EmbeddedResolutionTrait;
use QL\Hal\Api\Utility\HypermediaLinkTrait;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Helpers\UrlHelper;

/**
 * Push Object Normalizer
 */
class PushNormalizer
{
    use HypermediaLinkTrait;
    use HypermediaResourceTrait;
    use EmbeddedResolutionTrait;

    /**
     * @var UrlHelper
     */
    private $urls;

    /**
     * @var UserNormalizer
     */
    private $users;

    /**
     * @var BuildNormalizer
     */
    private $builds;

    /**
     * @var DeploymentNormalizer
     */
    private $deployments;

    /**
     * @var array
     */
    private $embed;

    /**
     * @param UrlHelper $urls
     * @param UserNormalizer $users
     * @param BuildNormalizer $builds
     * @param DeploymentNormalizer $deployments
     */
    public function __construct(
        UrlHelper $urls,
        UserNormalizer $users,
        BuildNormalizer $builds,
        DeploymentNormalizer $deployments
    ) {
        $this->urls = $urls;
        $this->users = $users;
        $this->builds = $builds;
        $this->deployments = $deployments;

        $this->embed = [];
    }

    /**
     * @param Push $push
     * @return array
     */
    public function link(Push $push)
    {
        return $this->buildLink(
            ['api.push', ['id' => $push->getId()]],
            [
                'title' => $push->getId()
            ]
        );
    }

    /**
     * @param Push $push
     * @param array $embed
     * @return array
     */
    public function resource(Push $push, array $embed = [])
    {
        $properties = [
            'user' => $push->getUser(),
            'build' => $push->getBuild(),
            'deployment' => $push->getDeployment()
        ];

        return $this->buildResource(
            [
                'id' => $push->getId(),
                'status' => $push->getStatus(),
                'created' => $push->getCreated(),
                'start' => $push->getStart(),
                'end' => $push->getEnd(),
                'url' => $this->urls->urlFor('push', ['push' => $push->getId()])
            ],
            $this->resolveEmbedded($properties, array_merge($this->embed, $embed)),
            [
                'self' => $this->link($push),
                'user' => $this->users->link($push->getUser()),
                'build' => $this->builds->link($push->getBuild()),
                'deployment' => $this->deployments->link($push->getDeployment()),
                'logs' => $this->buildLink(['api.push.logs', ['id' => $push->getId()]])
            ]
        );
    }
}