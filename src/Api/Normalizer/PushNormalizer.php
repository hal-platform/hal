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
     * @var RepositoryNormalizer
     */
    private $repositories;

    /**
     * @var array
     */
    private $embed;

    /**
     * @param UrlHelper $urls
     * @param UserNormalizer $users
     * @param BuildNormalizer $builds
     * @param DeploymentNormalizer $deployments
     * @param RepositoryNormalizer $repositories
     */
    public function __construct(
        UrlHelper $urls,
        UserNormalizer $users,
        BuildNormalizer $builds,
        DeploymentNormalizer $deployments,
        RepositoryNormalizer $repositories
    ) {
        $this->urls = $urls;
        $this->users = $users;
        $this->builds = $builds;
        $this->deployments = $deployments;
        $this->repositories = $repositories;

        $this->embed = [];
    }

    /**
     * @param Push $push
     * @return array
     */
    public function link(Push $push = null)
    {
        return  (is_null($push)) ? null : $this->buildLink(
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
    public function resource(Push $push = null, array $embed = [])
    {
        if (is_null($push)) {
            return null;
        }

        $properties = [
            'user' => $push->getUser(),
            'build' => $push->getBuild(),
            'deployment' => $push->getDeployment(),
            'repository' => $push->getRepository()
        ];

        return $this->buildResource(
            [
                'id' => $push->getId(),
                'status' => $push->getStatus(),
                'url' => $this->urls->urlFor('push', ['push' => $push->getId()]),
                'created' => $push->getCreated(),
                'start' => $push->getStart(),
                'end' => $push->getEnd()
            ],
            $this->resolveEmbedded($properties, array_merge($this->embed, $embed)),
            [
                'self' => $this->link($push),
                'user' => $this->users->link($push->getUser()),
                'build' => $this->builds->link($push->getBuild()),
                'deployment' => $this->deployments->link($push->getDeployment()),
                'repository' => $this->repositories->link($push->getRepository()),
                'logs' => $this->buildLink(['api.push.logs', ['id' => $push->getId()]])
            ]
        );
    }
}
