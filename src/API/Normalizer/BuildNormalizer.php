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
use QL\Hal\Core\Entity\Build;

class BuildNormalizer implements NormalizerInterface
{
    use HypermediaResourceTrait;
    use EmbeddedResolutionTrait;

    /**
     * @var GitHubURLBuilder
     */
    private $urlBuilder;

    /**
     * @var UserNormalizer
     */
    private $userNormalizer;

    /**
     * @var ApplicationNormalizer
     */
    private $appNormalizer;

    /**
     * @var EnvironmentNormalizer
     */
    private $envNormalizer;

    /**
     * @var array
     */
    private $embed;

    /**
     * @param GitHubURLBuilder $urlBuilder
     *
     * @param UserNormalizer $userNormalizer
     * @param ApplicationNormalizer $appNormalizer
     * @param EnvironmentNormalizer $envNormalizer
     */
    public function __construct(
        GitHubURLBuilder $urlBuilder,

        UserNormalizer $userNormalizer,
        ApplicationNormalizer $appNormalizer,
        EnvironmentNormalizer $envNormalizer
    ) {
        $this->urlBuilder = $urlBuilder;

        $this->userNormalizer = $userNormalizer;
        $this->appNormalizer = $appNormalizer;
        $this->envNormalizer = $envNormalizer;

        $this->embed = [];
    }

    /**
     * @param Build $input
     *
     * @return array|null
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
    public function link(Build $build = null): ?Hyperlink
    {
        if (!$build) {
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
     * @return array|null
     */
    public function resource(Build $build = null, array $embed = [])
    {
        if (is_null($build)) {
            return null;
        }

        $properties = [
            'user' => $build->user(),
            'application' => $build->application(),
            'environment' => $build->environment()
        ];

        $data = [
            'id' => $build->id(),
            'status' => $build->status(),

            'created' => $build->created(),
            'start' => $build->start(),
            'end' => $build->end(),

            'reference' => $build->branch(),
            'commit' => $build->commit()
        ];

        return $this->buildResource(
            $data,
            $this->resolveEmbedded($properties, array_merge($this->embed, $embed)),
            $this->buildLinks($build)
        );
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

        $self = [
            'self' => $this->link($build)
        ];

        $links = [
            'application' => $this->appNormalizer->link($build->application()),
            'environment' => $this->envNormalizer->link($build->environment()),
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

        if ($build->user()) {
            $self += [
                'user' => $this->userNormalizer->link($build->user())
            ];
        }

        if ($build->status() === 'Success') {
            $pages += [
                'start_push_page' => new Hyperlink(
                    ['push.start', ['build' => $build->id()]],
                    '',
                    'text/html'
                )
            ];
        }

        return $self + $links + $pages;
    }
}
