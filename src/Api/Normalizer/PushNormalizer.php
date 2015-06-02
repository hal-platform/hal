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
     * @var ApplicationNormalizer
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
     * @param ApplicationNormalizer $repositories
     */
    public function __construct(
        UrlHelper $urls,
        UserNormalizer $users,
        BuildNormalizer $builds,
        DeploymentNormalizer $deployments,
        ApplicationNormalizer $repositories
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
            ['api.push', ['id' => $push->id()]],
            [
                'title' => $push->id()
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
            'user' => $push->user(),
            'build' => $push->build(),
            'deployment' => $push->deployment(),
            'application' => $push->application()
        ];

        return $this->buildResource(
            [
                'id' => $push->id(),
                'status' => $push->status(),
                'url' => $this->urls->urlFor('push', ['push' => $push->id()]),
                'created' => $push->created(),
                'start' => $push->start(),
                'end' => $push->end()
            ],
            $this->resolveEmbedded($properties, array_merge($this->embed, $embed)),
            [
                'self' => $this->link($push),
                'user' => $this->users->link($push->user()),
                'build' => $this->builds->link($push->build()),
                'deployment' => $this->deployments->link($push->deployment()),
                'application' => $this->repositories->link($push->application()),
                'logs' => $this->buildLink(['api.push.logs', ['id' => $push->id()]])
            ]
        );
    }
}
