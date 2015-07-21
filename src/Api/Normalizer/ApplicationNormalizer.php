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
use QL\Hal\Core\Entity\Application;
use QL\Hal\Github\GitHubURLBuilder;
use QL\Panthor\Utility\Url;

class ApplicationNormalizer
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
     * @var GroupNormalizer
     */
    private $groupNormalizer;

    /**
     * @var array
     */
    private $embed;

    /**
     * @param Url $url
     * @param GitHubURLBuilder $urlBuilder
     * @param GroupNormalizer $groupNormalizer
     */
    public function __construct(Url $url, GitHubURLBuilder $urlBuilder, GroupNormalizer $groupNormalizer)
    {
        $this->url = $url;
        $this->urlBuilder = $urlBuilder;
        $this->groupNormalizer = $groupNormalizer;

        $this->embed = [];
    }

    /**
     * @param Application|null $application
     *
     * @return array|null
     */
    public function link(Application $application = null)
    {
        if (!$application) {
            return null;
        }

        return $this->buildLink(
            [
                'api.application', ['id' => $application->id()]
            ],
            [
                'title' => $application->name()
            ]
        );
    }

    /**
     * @param Application|null $application
     * @param array $embed
     *
     * @return array|null
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
                'name' => $application->name(),

                'email' => $application->email(),
                'eb_name' => $application->ebName()
            ],
            $this->resolveEmbedded($properties, array_merge($this->embed, $embed)),
            [
                'self' => $this->link($application),
                'group' => $this->groupNormalizer->link($application->group()),
                'deployments' => $this->buildLink(['api.deployments', ['id' => $application->id()]]),
                'builds' => $this->buildLink(['api.builds', ['id' => $application->id()]]),
                'pushes' => $this->buildLink(['api.pushes', ['id' => $application->id()]]),

                'page' => $this->buildLink(
                    ['application', ['application' => $application->id()]],
                    [
                        'title' => $application->name(),
                        'type' => 'text/html'
                    ]
                ),
                'status_page' => $this->buildLink(
                    ['application.status', ['application' => $application->id()]],
                    [
                        'title' => sprintf('%s Status', $application->name()),
                        'type' => 'text/html'
                    ]
                ),
                'github_page' => $this->buildLink(
                    $this->urlBuilder->githubRepoURL($application->githubOwner(), $application->githubRepo())
                )
            ]
        );
    }
}
