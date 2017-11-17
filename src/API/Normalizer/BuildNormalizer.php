<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\API\Normalizer;

use Hal\Core\Entity\Build;
use Hal\UI\API\Hyperlink;
use Hal\UI\API\HypermediaResource;
use Hal\UI\API\ResourceNormalizerInterface;
use Hal\UI\Github\GitHubURLBuilder;

class BuildNormalizer implements ResourceNormalizerInterface
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
     * @param Build $input
     *
     * @return mixed
     */
    public function normalize($input)
    {
        return $this->resource($input);
    }

    /**
     * @param Build|null $build
     *
     * @return Hyperlink|null
     */
    public function link($build): ?Hyperlink
    {
        if (!$build instanceof Build) {
            return null;
        }

        return new Hyperlink(
            ['api.build', ['build' => $build->id()]],
            $build->id()
        );
    }

    /**
     * @param Build|null $build
     * @param array $embed
     *
     * @return HypermediaResource|null
     */
    public function resource($build, array $embed = []): ?HypermediaResource
    {
        if (!$build instanceof Build) {
            return null;
        }

        $data = [
            'id' => $build->id(),
            'status' => $build->status(),

            'created' => $build->created(),
            'start' => $build->start(),
            'end' => $build->end(),

            'reference' => $build->branch(),
            'commit' => $build->commit()
        ];

        $resource = new HypermediaResource($data, $this->buildLinks($build), [
            'user' => $build->user(),
            'application' => $build->application(),
            'environment' => $build->environment()
        ]);

        $resource->withEmbedded($embed);

        return $resource;
    }

    /**
     * @param Build $build
     *
     * @return array
     */
    private function buildLinks(Build $build)
    {
        $ghOwner = $build->application()->githubOwner();
        $ghRepo = $build->application()->githubRepo();

        $links = [
            'self' => $this->link($build),
            'events' => new Hyperlink(['api.build.events', ['build' => $build->id()]]),
        ];

        $pages = [
            'page' => new Hyperlink(
                ['build', ['build' => $build->id()]],
                '',
                'text/html'
            ),
            'github_reference_page' => new Hyperlink(
                $this->urlBuilder->githubReferenceURL($ghOwner, $ghRepo, $build->branch()),
                '',
                'text/html'
            ),
            'github_commit_page' => new Hyperlink(
                $this->urlBuilder->githubCommitURL($ghOwner, $ghRepo, $build->commit()),
                '',
                'text/html'
            )
        ];

        if ($build->status() === 'Success') {
            $pages += [
                'start_push_page' => new Hyperlink(
                    ['push.start', ['build' => $build->id()]],
                    '',
                    'text/html'
                )
            ];
        }

        return $links + $pages;
    }
}
