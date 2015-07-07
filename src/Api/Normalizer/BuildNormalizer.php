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
use QL\Hal\Core\Entity\Build;
use QL\Hal\Github\GitHubURLBuilder;
use QL\Panthor\Utility\Url;

class BuildNormalizer
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
     * @param Url $url
     * @param GitHubURLBuilder $urlBuilder
     *
     * @param UserNormalizer $userNormalizer
     * @param ApplicationNormalizer $appNormalizer
     * @param EnvironmentNormalizer $envNormalizer
     */
    public function __construct(
        Url $url,
        GitHubURLBuilder $urlBuilder,

        UserNormalizer $userNormalizer,
        ApplicationNormalizer $appNormalizer,
        EnvironmentNormalizer $envNormalizer
    ) {
        $this->url = $url;
        $this->urlBuilder = $urlBuilder;

        $this->userNormalizer = $userNormalizer;
        $this->appNormalizer = $appNormalizer;
        $this->envNormalizer = $envNormalizer;

        $this->embed = [];
    }

    /**
     * @param Build|null $build
     *
     * @return array|null
     */
    public function link(Build $build = null)
    {
        if (!$build) {
            return null;
        }

        return $this->buildLink(
            [
                'api.build', ['id' => $build->id()]
            ],
            [
                'title' => $build->id()
            ]
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


        return $this->buildResource(
            [
                'id' => $build->id(),
                'status' => $build->status(),

                'created' => $build->created(),
                'start' => $build->start(),
                'end' => $build->end(),

                'reference' => $build->branch(),
                'commit' => $build->commit()
            ],
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
            'logs' => $this->buildLink(['api.build.logs', ['id' => $build->id()]]),
        ];

        $pages = [
            'page' => $this->buildLink(
                ['build', ['build' => $build->id()]],
                [
                    'type' => 'text/html'
                ]
            ),
            'github_reference_page' => $this->buildLink(
                $this->urlBuilder->githubCommitURL($ghOwner, $ghRepo, $build->commit()),
                [
                    'type' => 'text/html'
                ]
            ),
            'github_commit_page' => $this->buildLink(
                $this->urlBuilder->githubReferenceURL($ghOwner, $ghRepo, $build->branch()),
                [
                    'type' => 'text/html'
                ]
            )
        ];

        if ($build->user()) {
            $self += [
                'user' => $this->userNormalizer->link($build->user())
            ];
        }

        if ($build->status() === 'Success') {
            $pages += [
                'start_push_page' => $this->buildLink(
                    ['push.start', ['build' => $build->id()]],
                    [
                        'type' => 'text/html'
                    ]
                )
            ];
        }

        return $self + $links + $pages;
    }
}
