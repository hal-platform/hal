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
     * @var RepositoryNormalizer
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
     * @param RepositoryNormalizer $repositories
     * @param EnvironmentNormalizer $environments
     */
    public function __construct(
        UrlHelper $urls,
        UserNormalizer $users,
        RepositoryNormalizer $repositories,
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
    public function link(Build $build)
    {
        return $this->buildLink(
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
    public function resource(Build $build, array $embed = [])
    {
        $properties = [
            'initiator' => $build->getUser(),
            'repository' => $build->getRepository(),
            'environment' => $build->getEnvironment()
        ];

        return $this->buildResource(
            [
                'id' => $build->getId(),
                'status' => $build->getStatus(),
                'created' => $build->getCreated(),
                'start' => $build->getStart(),
                'end' => $build->getEnd(),
                'url' => $this->urls->urlFor('build', ['build' => $build->getId()]),
                'source' => [
                    'reference' => $build->getBranch(),
                    'commit' => $build->getCommit(),
                    'url' => $this->urls->githubCommitUrl(
                        $build->getRepository()->getGithubUser(),
                        $build->getRepository()->getGithubRepo(),
                        $build->getCommit()
                    ),
                    'browse' => $this->urls->githubReferenceUrl(
                        $build->getRepository()->getGithubUser(),
                        $build->getRepository()->getGithubRepo(),
                        $build->getBranch()
                    ),
                ]
            ],
            $this->resolveEmbedded($properties, array_merge($this->embed, $embed)),
            [
                'self' => $this->link($build),
                'initiator' => $this->users->link($build->getUser()),
                'repository' => $this->repositories->link($build->getRepository()),
                'environment' => $this->environments->link($build->getEnvironment()),
                'logs' => $this->buildLink(['api.build.logs', ['id' => $build->getId()]]),
            ]
        );
    }
}