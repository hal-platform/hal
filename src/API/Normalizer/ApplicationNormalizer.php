<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\API\Normalizer;

use Hal\Core\Entity\Application;
use Hal\Core\Parameters;
use Hal\Core\VersionControl\VCSFactory;
use Hal\UI\API\Hyperlink;
use Hal\UI\API\HypermediaResource;
use Hal\UI\API\ResourceNormalizerInterface;

class ApplicationNormalizer implements ResourceNormalizerInterface
{
    /**
     * @var VCSFactory
     */
    private $vcs;

    /**
     * @param VCSFactory $vcs
     */
    public function __construct(VCSFactory $vcs)
    {
        $this->vcs = $vcs;
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
            'name' => $application->name(),
        ];

        $links = [
            'self' => $this->link($application),
            'targets' => new Hyperlink(['api.targets', ['application' => $application->id()]]),
            'builds' => new Hyperlink(['api.builds', ['application' => $application->id()]]),
            'releases' => new Hyperlink(['api.releases', ['application' => $application->id()]]),

            'page' => new Hyperlink(
                ['application', ['application' => $application->id()]],
                $application->name(),
                'text/html',
            ),
            'status_page' => new Hyperlink(
                ['application.dashboard', ['application' => $application->id()]],
                sprintf('%s Status', $application->name()),
                'text/html',
            ),
        ];

        if ($hyperlink = $this->getVCSURL($application)) {
            $links += [
                'github_page' => $hyperlink,
            ];
        }

        $resource = new HypermediaResource($data, $links, [
            'organization' => $application->organization(),
            'vcs_provider' => $application->provider(),
        ]);

        $resource->withEmbedded($embed);

        return $resource;
    }

    /**
     * @param Application $application
     *
     * @return Hyperlink|null
     */
    private function getVCSURL(Application $application)
    {
        $url = '';

        if ($provider = $application->provider()) {
            if ($client = $this->vcs->authenticate($provider)) {
                $url = $client->urlForRepository(
                    $application->parameter(Parameters::VC_GH_OWNER),
                    $application->parameter(Parameters::VC_GH_REPO)
                );
            }
        }

        if (!$url) {
            return null;
        }

        return new Hyperlink($url, '', 'text/html');
    }
}
