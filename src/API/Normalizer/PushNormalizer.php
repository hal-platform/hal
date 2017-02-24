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
use QL\Hal\Core\Entity\Push;

class PushNormalizer implements NormalizerInterface
{
    use HypermediaResourceTrait;
    use EmbeddedResolutionTrait;

    /**
     * @var UserNormalizer
     */
    private $userNormalizer;

    /**
     * @var BuildNormalizer
     */
    private $buildNormalizer;

    /**
     * @var DeploymentNormalizer
     */
    private $deploymentNormalizer;

    /**
     * @var ApplicationNormalizer
     */
    private $appNormalizer;

    /**
     * @var array
     */
    private $embed;

    /**
     * @param UserNormalizer $userNormalizer
     * @param BuildNormalizer $buildNormalizer
     * @param DeploymentNormalizer $deploymentNormalizer
     * @param ApplicationNormalizer $appNormalizer
     */
    public function __construct(
        UserNormalizer $userNormalizer,
        BuildNormalizer $buildNormalizer,
        DeploymentNormalizer $deploymentNormalizer,
        ApplicationNormalizer $appNormalizer
    ) {
        $this->userNormalizer = $userNormalizer;
        $this->buildNormalizer = $buildNormalizer;
        $this->deploymentNormalizer = $deploymentNormalizer;
        $this->appNormalizer = $appNormalizer;

        $this->embed = [];
    }

    /**
     * @param Push $input
     *
     * @return array|null
     */
    public function normalize($input)
    {
        return $this->resource($input);
    }

    /**
     * @param Push|null $push
     *
     * @return Hyperlink|null
     */
    public function link(Push $push = null): ?Hyperlink
    {
        if (!$push) {
            return null;
        }

        return new Hyperlink(
            ['api.push', ['id' => $push->id()]],
            $push->id()
        );
    }

    /**
     * @param Push|null $push
     * @param array $embed
     *
     * @return array|null
     */
    public function resource(Push $push = null, array $embed = [])
    {
        if (is_null($push)) {
            return null;
        }

        $properties = [
            'user' => $push->user(),
            'build' => $push->build(),
            'deployment' => $push->deployment(),
            'application' => $push->application()
        ];

        $data = [
            'id' => $push->id(),
            'status' => $push->status(),

            'created' => $push->created(),
            'start' => $push->start(),
            'end' => $push->end()
        ];

        return $this->buildResource(
            $data,
            $this->resolveEmbedded($properties, array_merge($this->embed, $embed)),
            $this->buildLinks($push)
        );
    }

    /**
     * @param Push $push
     *
     * @return array
     */
    private function buildLinks(Push $push)
    {
        $self = [
            'self' => $this->link($push)
        ];

        $links = [
            'build' => $this->buildNormalizer->link($push->build()),
            'application' => $this->appNormalizer->link($push->application()),
            'logs' => new Hyperlink(['api.push.logs', ['id' => $push->id()]])
        ];

        $pages = [
            'page' => new Hyperlink(
                ['push', ['push' => $push->id()]],
                '',
                'text/html'
            )
        ];

        if ($push->user()) {
            $self += [
                'user' => $this->userNormalizer->link($push->user())
            ];
        }

        if ($push->deployment()) {
            $self += [
                'deployment' => $this->deploymentNormalizer->link($push->deployment())
            ];
        }

        return $self + $links + $pages;
    }
}
