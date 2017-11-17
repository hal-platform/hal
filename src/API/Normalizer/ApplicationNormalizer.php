<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\API\Normalizer;

use Hal\Core\Entity\Application;
use Hal\UI\API\Hyperlink;
use Hal\UI\API\HypermediaResource;
use Hal\UI\API\ResourceNormalizerInterface;
use Hal\UI\Github\GitHubURLBuilder;

class ApplicationNormalizer implements ResourceNormalizerInterface
{
    /**
     * @var GitHubURLBuilder
     */
    private $urlBuilder;

    /**
     * @param GitHubURLBuilder $urlBuilder
     */
    public function __construct(GitHubURLBuilder $urlBuilder)
    {
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @param Application $input
     *
     * @return mixed
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
    public function link($application): ?Hyperlink
    {
        if (!$application instanceof Application) {
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
     * @return HypermediaResource|null
     */
    public function resource($application, array $embed = []): ?HypermediaResource
    {
        if (!$application instanceof Application) {
            return null;
        }

        $data = [
            'id' => $application->id(),
            'key' => $application->identifier(),
            'name' => $application->name()
        ];

        $links = [
            'self' => $this->link($application),
            'targets' => new Hyperlink(['api.targets', ['application' => $application->id()]]),
            'builds' => new Hyperlink(['api.builds', ['application' => $application->id()]]),
            'releases' => new Hyperlink(['api.releases', ['application' => $application->id()]]),

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
                $this->urlBuilder->githubRepoURL($application->github()->owner(), $application->github()->repository()),
                '',
                'text/html'
            )
        ];

        $resource = new HypermediaResource($data, $links, [
            'organization' => $application->organization()
        ]);

        $resource->withEmbedded($embed);

        return $resource;
    }
}
