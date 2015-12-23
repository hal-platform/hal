<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Api\Normalizer;

use QL\Hal\Api\Hyperlink;
use QL\Hal\Api\NormalizerInterface;
use QL\Hal\Api\Utility\EmbeddedResolutionTrait;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Push;
use QL\Panthor\Utility\Url;

class PushNormalizer implements NormalizerInterface
{
    use HypermediaResourceTrait;
    use EmbeddedResolutionTrait;

    /**
     * @var Url
     */
    private $url;

    /**
     * @var UserNormalizer
     */
    private $userNormalizer;

    /**
     * @var BuildNormalizer
     */
    private $buildNormalizer;

    /**
     * @var DeploymentNormalizer
     */
    private $deploymentNormalizer;

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
     * @param UserNormalizer $userNormalizer
     * @param BuildNormalizer $buildNormalizer
     * @param DeploymentNormalizer $deploymentNormalizer
     * @param ApplicationNormalizer $appNormalizer
     */
    public function __construct(
        Url $url,
        UserNormalizer $userNormalizer,
        BuildNormalizer $buildNormalizer,
        DeploymentNormalizer $deploymentNormalizer,
        ApplicationNormalizer $appNormalizer
    ) {
        $this->url = $url;

        $this->userNormalizer = $userNormalizer;
        $this->buildNormalizer = $buildNormalizer;
        $this->deploymentNormalizer = $deploymentNormalizer;
        $this->appNormalizer = $appNormalizer;

        $this->embed = [];
    }

    /**
     * @param Push $input
     *
     * @return array
     */
    public function normalize($input)
    {
        return $this->resource($input);
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

        return new Hyperlink(
            ['api.push', ['id' => $push->id()]],
            $push->id()
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

                'created' => $push->created(),
                'start' => $push->start(),
                'end' => $push->end()
            ],
            $this->resolveEmbedded($properties, array_merge($this->embed, $embed)),
            $this->buildLinks($push)
        );
    }

    /**
     * @param Push $push
     *
     * @return array
     */
    private function buildLinks(Push $push)
    {
        $self = [
            'self' => $this->link($push)
        ];

        $links = [
            'build' => $this->buildNormalizer->link($push->build()),
            'application' => $this->appNormalizer->link($push->application()),
            'logs' => new Hyperlink(['api.push.logs', ['id' => $push->id()]])
        ];

        $pages = [
            'page' => new Hyperlink(
                ['push', ['push' => $push->id()]],
                null,
                'text/html'
            )
        ];

        if ($push->user()) {
            $self += [
                'user' => $this->userNormalizer->link($push->user())
            ];
        }

        if ($push->deployment()) {
            $self += [
                'deployment' => $this->deploymentNormalizer->link($push->deployment())
            ];
        }

        return $self + $links + $pages;
    }
}
