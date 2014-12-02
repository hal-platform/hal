<?php

namespace QL\Hal\Api\Normalizer;

use QL\Hal\Api\Utility\EmbeddedResolutionTrait;
use QL\Hal\Api\Utility\HypermediaLinkTrait;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Repository;
use QL\Hal\Helpers\UrlHelper;

/**
 * Repository Object Normalizer
 */
class RepositoryNormalizer
{
    use HypermediaLinkTrait;
    use HypermediaResourceTrait;
    use EmbeddedResolutionTrait;

    /**
     * @var UrlHelper
     */
    private $url;

    /**
     * @var GroupNormalizer
     */
    private $groups;

    /**
     * @var array
     */
    private $embed;

    /**
     * @param UrlHelper $url
     * @param GroupNormalizer $groups
     */
    public function __construct(
        UrlHelper $url,
        GroupNormalizer $groups
    ) {
        $this->url = $url;
        $this->groups = $groups;

        $this->embed = [];
    }

    /**
     * @param Repository $repository
     * @return array
     */
    public function link(Repository $repository)
    {
        return $this->buildLink(
            ['api.repository', ['id' => $repository->getId()]],
            [
                'title' => $repository->getKey()
            ]
        );
    }

    /**
     * @param Repository $repository
     * @param array $embed
     * @return array
     */
    public function resource(Repository $repository, array $embed = [])
    {
        $properties = [
            'group' => $repository->getGroup()
        ];

        return $this->buildResource(
            [
                'id' => $repository->getId(),
                'identifier' => $repository->getKey(),
                'title' => $repository->getDescription(),
                'email' => $repository->getEmail(),
                'github' => [
                    'user' => $repository->getGithubUser(),
                    'repository' => $repository->getGithubRepo(),
                    'url' => $this->url->githubRepoUrl($repository->getGithubUser(), $repository->getGithubRepo())
                ],
                'commands' => [
                    'build' => $repository->getBuildCmd(),
                    'build-transform' => $repository->getBuildTransformCmd(),
                    'pre-push' => $repository->getPrePushCmd(),
                    'post-push' => $repository->getPostPushCmd()
                ]
            ],
            $this->resolveEmbedded($properties, array_merge($this->embed, $embed)),
            [
                'self' => $this->link($repository),
                'group' => $this->groups->link($repository->getGroup())
            ]
        );
    }
}