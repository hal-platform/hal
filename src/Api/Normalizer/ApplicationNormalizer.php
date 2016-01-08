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
use QL\Hal\Core\Entity\Application;
use QL\Hal\Github\GitHubURLBuilder;
use QL\Panthor\Utility\Url;

class ApplicationNormalizer implements NormalizerInterface
{
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
     * @param Application $input
     *
     * @return array
     */
    public function normalize($input)
    {
        return $this->resource($input);
    }

    /**
     * @param Application|null $application
     *
     * @return Hyperlink|null
     */
    public function link(Application $application = null)
    {
        if (!$application) {
            return null;
        }

        return new Hyperlink(
            ['api.application', ['id' => $application->id()]],
            $application->name()
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

                'email' => $application->email()
            ],
            $this->resolveEmbedded($properties, array_merge($this->embed, $embed)),
            [
                'self' => $this->link($application),
                'group' => $this->groupNormalizer->link($application->group()),
                'deployments' => new Hyperlink(['api.deployments', ['id' => $application->id()]]),
                'builds' => new Hyperlink(['api.builds', ['id' => $application->id()]]),
                'pushes' => new Hyperlink(['api.pushes', ['id' => $application->id()]]),

                'page' => new Hyperlink(
                    ['application', ['application' => $application->id()]],
                    $application->name(),
                    'text/html'
                ),
                'status_page' => new Hyperlink(
                    ['application.status', ['application' => $application->id()]],
                    sprintf('%s Status', $application->name()),
                    'text/html'
                ),
                'github_page' => new Hyperlink(
                    $this->urlBuilder->githubRepoURL($application->githubOwner(), $application->githubRepo()),
                    '',
                    'text/html'
                )
            ]
        );
    }
}
