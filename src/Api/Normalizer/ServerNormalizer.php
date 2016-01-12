<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Api\Normalizer;

use QL\Hal\Api\Hyperlink;
use QL\Hal\Api\NormalizerInterface;
use QL\Hal\Api\Utility\EmbeddedResolutionTrait;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Server;
use QL\Hal\Core\Type\EnumType\ServerEnum;
use QL\Hal\Core\Utility\SortingTrait;

class ServerNormalizer implements NormalizerInterface
{
    use HypermediaResourceTrait;
    use EmbeddedResolutionTrait;
    use SortingTrait;

    /**
     * @type EnvironmentNormalizer
     */
    private $normalizer;

    /**
     * @type array
     */
    private $embed;

    /**
     * @param EnvironmentNormalizer $normalizer
     */
    public function __construct(EnvironmentNormalizer $normalizer)
    {
        $this->normalizer = $normalizer;

        $this->embed = ['environment'];
    }

    /**
     * @param Server $input
     *
     * @return array
     */
    public function normalize($input)
    {
        return $this->resource($input);
    }

    /**
     * @param Server $server
     * @return array
     */
    public function link(Server $server = null)
    {
        if (!$server) {
            return null;
        }

        return new Hyperlink(
            ['api.server', ['id' => $server->id()]],
            $server->formatPretty()
        );
    }

    /**
     * @param Server $server
     * @param array $embed
     * @return array
     */
    public function resource(Server $server = null, array $embed = [])
    {
        if (!$server) {
            return null;
        }

        $properties = [
            'environment' => $server->environment(),
            'deployments' => $server->deployments()->toArray()
        ];

        // shitty, but this is a circular reference so deal with it
        $linkedDeployments = [];
        $deployments = $server->deployments()->toArray();
        usort($deployments, $this->deploymentSorter());

        foreach ($deployments as $deployment) {
            $linkedDeployments[] = new Hyperlink(
                ['api.deployment', ['id' => $deployment->id()]],
                $deployment->formatPretty(true)
            );
        }

        return $this->buildResource(
            [
                'id' => $server->id(),
                'type' => $server->type(),
                'name' => $server->name()
            ],
            $this->resolveEmbedded($properties, array_merge($this->embed, $embed)),
            [
                'self' => $this->link($server),
                'environment' => $this->normalizer->link($server->environment()),
                'deployments' => $linkedDeployments
            ]
        );
    }
}
