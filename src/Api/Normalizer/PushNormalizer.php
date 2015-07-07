<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Api\Normalizer;

use QL\Hal\Api\Utility\EmbeddedResolutionTrait;
use QL\Hal\Api\Utility\HypermediaLinkTrait;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Push;
use QL\Panthor\Utility\Url;

/**
 * Push Object Normalizer
 */
class PushNormalizer
{
    use HypermediaLinkTrait;
    use HypermediaResourceTrait;
    use EmbeddedResolutionTrait;

    /**
     * @var Url
     */
    private $url;

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
    private $appNormalizer;

    /**
     * @var array
     */
    private $embed;

    /**
     * @param Url $url
     * @param UserNormalizer $users
     * @param BuildNormalizer $builds
     * @param DeploymentNormalizer $deployments
     * @param ApplicationNormalizer $appNormalizer
     */
    public function __construct(
        Url $url,
        UserNormalizer $users,
        BuildNormalizer $builds,
        DeploymentNormalizer $deployments,
        ApplicationNormalizer $appNormalizer
    ) {
        $this->url = $url;

        $this->users = $users;
        $this->builds = $builds;
        $this->deployments = $deployments;
        $this->appNormalizer = $appNormalizer;

        $this->embed = [];
    }

    /**
     * @param Push|null $push
     *
     * @return array|null
     */
    public function link(Push $push = null)
    {
        if (!$push) {
            return null;
        }

        return $this->buildLink(
            [
                'api.push', ['id' => $push->id()]
            ],
            [
                'title' => $push->id()
            ]
        );
    }

    /**
     * @param Push|null $push
     * @param array $embed
     *
     * @return array|null
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
                'url' => $this->url->absoluteUrlFor('push', ['push' => $push->id()]),
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
                'application' => $this->appNormalizer->link($push->application()),
                'logs' => $this->buildLink(['api.push.logs', ['id' => $push->id()]])
            ]
        );
    }
}
