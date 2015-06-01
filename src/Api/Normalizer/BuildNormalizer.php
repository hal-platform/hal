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
            ['api.build', ['id' => $build->getId()]],
            [
                'title' => $build->getId()
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
            'user' => $build->getUser(),
            'application' => $build->getApplication(),
            'environment' => $build->getEnvironment()
        ];

        return $this->buildResource(
            [
                'id' => $build->getId(),
                'status' => $build->getStatus(),
                'url' => $this->urls->urlFor('build', ['build' => $build->getId()]),
                'created' => $build->getCreated(),
                'start' => $build->getStart(),
                'end' => $build->getEnd(),
                'reference' => [
                    'text' => $build->getBranch(),
                    'url' => $this->urls->githubReferenceUrl(
                        $build->getApplication()->githubUser(),
                        $build->getApplication()->githubRepo(),
                        $build->getBranch()
                    )
                ],
                'commit' => [
                    'text' => $build->getCommit(),
                    'url' => $this->urls->githubCommitUrl(
                        $build->getApplication()->githubUser(),
                        $build->getApplication()->githubRepo(),
                        $build->getCommit()
                    )
                ]
            ],
            $this->resolveEmbedded($properties, array_merge($this->embed, $embed)),
            [
                'self' => $this->link($build),
                'user' => $this->users->link($build->getUser()),
                'application' => $this->repositories->link($build->getApplication()),
                'environment' => $this->environments->link($build->getEnvironment()),
                'logs' => $this->buildLink(['api.build.logs', ['id' => $build->getId()]]),
            ]
        );
    }
}
