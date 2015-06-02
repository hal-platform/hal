<?php

namespace QL\Hal\Api\Normalizer;

use QL\Hal\Api\Utility\EmbeddedResolutionTrait;
use QL\Hal\Api\Utility\HypermediaLinkTrait;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Helpers\UrlHelper;

/**
 * Build Object Normalizer
 */
class BuildNormalizer
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
     * @var ApplicationNormalizer
     */
    private $repositories;

    /**
     * @var EnvironmentNormalizer
     */
    private $environments;

    /**
     * @var array
     */
    private $embed;

    /**
     * @param UrlHelper $urls
     * @param UserNormalizer $users
     * @param ApplicationNormalizer $repositories
     * @param EnvironmentNormalizer $environments
     */
    public function __construct(
        UrlHelper $urls,
        UserNormalizer $users,
        ApplicationNormalizer $repositories,
        EnvironmentNormalizer $environments
    ) {
        $this->urls = $urls;
        $this->users = $users;
        $this->repositories = $repositories;
        $this->environments = $environments;

        $this->embed = [];
    }

    /**
     * @param Build $build
     * @return array
     */
    public function link(Build $build = null)
    {
        return (is_null($build)) ? null : $this->buildLink(
            ['api.build', ['id' => $build->id()]],
            [
                'title' => $build->id()
            ]
        );
    }

    /**
     * @param Build $build
     * @param array $embed
     * @return array
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
                'url' => $this->urls->urlFor('build', ['build' => $build->id()]),
                'created' => $build->created(),
                'start' => $build->start(),
                'end' => $build->end(),
                'reference' => [
                    'text' => $build->branch(),
                    'url' => $this->urls->githubReferenceUrl(
                        $build->application()->githubOwner(),
                        $build->application()->githubRepo(),
                        $build->branch()
                    )
                ],
                'commit' => [
                    'text' => $build->commit(),
                    'url' => $this->urls->githubCommitUrl(
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
                'application' => $this->repositories->link($build->application()),
                'environment' => $this->environments->link($build->environment()),
                'logs' => $this->buildLink(['api.build.logs', ['id' => $build->id()]]),
            ]
        );
    }
}
