<?php

namespace QL\Hal\Api\Normalizer;

use QL\Hal\Api\Utility\EmbeddedResolutionTrait;
use QL\Hal\Api\Utility\HypermediaLinkTrait;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Helpers\UrlHelper;

class ApplicationNormalizer
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
     * @param Application $application
     * @return array
     */
    public function link(Application $application = null)
    {
        return (is_null($application)) ? null : $this->buildLink(
            ['api.application', ['id' => $application->id()]],
            [
                'title' => $application->key()
            ]
        );
    }

    /**
     * @param Application $application
     * @param array $embed
     * @return array
     */
    public function resource(Application $application = null, array $embed = [])
    {
        if (is_null($application)) {
            return null;
        }

        $properties = [
            'group' => $application->group()
        ];

        return $this->buildResource(
            [
                'id' => $application->id(),
                'key' => $application->key(),
                'title' => $application->name(),

                // @todo put html urls in _links, with html media type?
                'url' => $this->url->urlFor('repository', ['id' => $application->id()]),
                'email' => $application->email(),
                'github-user' => [
                    'text' => $application->githubOwner(),
                    'url' => $this->url->githubUserUrl($application->githubOwner())
                ],
                'github-repository' => [
                    'text' => $application->githubRepo(),
                    'url' => $this->url->githubRepoUrl($application->githubOwner(), $application->githubRepo())
                ],
                'eb-name' => $application->ebName()
            ],
            $this->resolveEmbedded($properties, array_merge($this->embed, $embed)),
            [
                'self' => $this->link($application),
                'group' => $this->groups->link($application->group()),
                'deployments' => $this->buildLink(['api.deployments', ['id' => $application->id()]]),
                'builds' => $this->buildLink(['api.builds', ['id' => $application->id()]]),
                'pushes' => $this->buildLink(['api.pushes', ['id' => $application->id()]])
            ]
        );
    }
}
