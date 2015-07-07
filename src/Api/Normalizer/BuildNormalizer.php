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
use QL\Hal\Core\Entity\Build;
use QL\Hal\Github\GitHubURLBuilder;
use QL\Panthor\Utility\Url;

/**
 * Build Object Normalizer
 */
class BuildNormalizer
{
    use HypermediaLinkTrait;
    use HypermediaResourceTrait;
    use EmbeddedResolutionTrait;

    /**
     * @var Url
     */
    private $url;

    /**
     * @var GitHubURLBuilder
     */
    private $urlBuilder;

    /**
     * @var UserNormalizer
     */
    private $users;

    /**
     * @var ApplicationNormalizer
     */
    private $appNormalizer;

    /**
     * @var EnvironmentNormalizer
     */
    private $environments;

    /**
     * @var array
     */
    private $embed;

    /**
     * @param Url $url
     * @param GitHubURLBuilder $urlBuilder
     *
     * @param UserNormalizer $users
     * @param ApplicationNormalizer $appNormalizer
     * @param EnvironmentNormalizer $environments
     */
    public function __construct(
        Url $url,
        GitHubURLBuilder $urlBuilder,

        UserNormalizer $users,
        ApplicationNormalizer $appNormalizer,
        EnvironmentNormalizer $environments
    ) {
        $this->url = $url;
        $this->urlBuilder = $urlBuilder;

        $this->users = $users;
        $this->appNormalizer = $appNormalizer;
        $this->environments = $environments;

        $this->embed = [];
    }

    /**
     * @param Build|null $build
     *
     * @return array|null
     */
    public function link(Build $build = null)
    {
        if (!$build) {
            return null;
        }

        return $this->buildLink(
            [
                'api.build', ['id' => $build->id()]
            ],
            [
                'title' => $build->id()
            ]
        );
    }

    /**
     * @param Build|null $build
     * @param array $embed
     *
     * @return array|null
     */
    public function resource(Build $build = null, array $embed = [])
    {
        if (is_null($build)) {
            return null;
        }

        $properties = [
            'user' => $build->user(),
            'application' => $build->application(),
            'environment' => $build->environment()
        ];

        return $this->buildResource(
            [
                'id' => $build->id(),
                'status' => $build->status(),
                'url' => $this->url->absoluteUrlFor('build', ['build' => $build->id()]),
                'created' => $build->created(),
                'start' => $build->start(),
                'end' => $build->end(),
                'reference' => [
                    'text' => $build->branch(),
                    'url' => $this->urlBuilder->githubReferenceURL(
                        $build->application()->githubOwner(),
                        $build->application()->githubRepo(),
                        $build->branch()
                    )
                ],
                'commit' => [
                    'text' => $build->commit(),
                    'url' => $this->urlBuilder->githubCommitURL(
                        $build->application()->githubOwner(),
                        $build->application()->githubRepo(),
                        $build->commit()
                    )
                ]
            ],
            $this->resolveEmbedded($properties, array_merge($this->embed, $embed)),
            [
                'self' => $this->link($build),
                'user' => $this->users->link($build->user()),
                'application' => $this->appNormalizer->link($build->application()),
                'environment' => $this->environments->link($build->environment()),
                'logs' => $this->buildLink(['api.build.logs', ['id' => $build->id()]]),
            ]
        );
    }
}
