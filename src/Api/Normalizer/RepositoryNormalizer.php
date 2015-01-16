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
    public function link(Repository $repository = null)
    {
        return (is_null($repository)) ? null : $this->buildLink(
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
    public function resource(Repository $repository = null, array $embed = [])
    {
        if (is_null($repository)) {
            return null;
        }

        $properties = [
            'group' => $repository->getGroup()
        ];

        return $this->buildResource(
            [
                'id' => $repository->getId(),
                'key' => $repository->getKey(),
                'title' => $repository->getDescription(),

                // @todo put html urls in _links, with html media type?
                'url' => $this->url->urlFor('repository', ['id' => $repository->getId()]),
                'email' => $repository->getEmail(),
                'github-user' => [
                    'text' => $repository->getGithubUser(),
                    'url' => $this->url->githubUserUrl($repository->getGithubUser())
                ],
                'github-repository' => [
                    'text' => $repository->getGithubRepo(),
                    'url' => $this->url->githubRepoUrl($repository->getGithubUser(), $repository->getGithubRepo())
                ],
                'eb-name' => $repository->getEbName()
            ],
            $this->resolveEmbedded($properties, array_merge($this->embed, $embed)),
            [
                'self' => $this->link($repository),
                'group' => $this->groups->link($repository->getGroup()),
                'deployments' => $this->buildLink(['api.deployments', ['id' => $repository->getId()]]),
                'builds' => $this->buildLink(['api.builds', ['id' => $repository->getId()]]),
                'pushes' => $this->buildLink(['api.pushes', ['id' => $repository->getId()]])
            ]
        );
    }
}
