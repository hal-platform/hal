<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\API\Normalizer;

use Hal\UI\API\Hyperlink;
use Hal\UI\API\NormalizerInterface;
use Hal\UI\API\Utility\EmbeddedResolutionTrait;
use Hal\UI\API\Utility\HypermediaResourceTrait;
use Hal\UI\Github\GitHubURLBuilder;
use QL\Hal\Core\Entity\Application;

class ApplicationNormalizer implements NormalizerInterface
{
    use HypermediaResourceTrait;
    use EmbeddedResolutionTrait;

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
     * @param GitHubURLBuilder $urlBuilder
     * @param GroupNormalizer $groupNormalizer
     */
    public function __construct(GitHubURLBuilder $urlBuilder, GroupNormalizer $groupNormalizer)
    {
        $this->urlBuilder = $urlBuilder;
        $this->groupNormalizer = $groupNormalizer;

        $this->embed = [];
    }

    /**
     * @param Application $input
     *
     * @return array|null
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
    public function link(Application $application = null): ?Hyperlink
    {
        if (!$application) {
            return null;
        }

        return new Hyperlink(
            ['api.application', ['application' => $application->id()]],
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

        $data = [
            'id' => $application->id(),
            'key' => $application->key(),
            'name' => $application->name(),

            'email' => $application->email()
        ];

        $embedded = $this->resolveEmbedded($properties, array_merge($this->embed, $embed));

        $links = [
            'self' => $this->link($application),
            'group' => $this->groupNormalizer->link($application->group()),
            'deployments' => new Hyperlink(['api.targets', ['application' => $application->id()]]),
            'builds' => new Hyperlink(['api.builds', ['application' => $application->id()]]),
            'pushes' => new Hyperlink(['api.pushes', ['application' => $application->id()]]),

            'page' => new Hyperlink(
                ['application', ['application' => $application->id()]],
                $application->name(),
                'text/html'
            ),
            'status_page' => new Hyperlink(
                ['application.dashboard', ['application' => $application->id()]],
                sprintf('%s Status', $application->name()),
                'text/html'
            ),
            'github_page' => new Hyperlink(
                $this->urlBuilder->githubRepoURL($application->githubOwner(), $application->githubRepo()),
                '',
                'text/html'
            )
        ];

        return $this->buildResource($data, $embedded, $links);
    }
}
