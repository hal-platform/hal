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
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Server;
use QL\Hal\Core\Utility\SortingTrait;

class ServerNormalizer implements NormalizerInterface
{
    use HypermediaResourceTrait;
    use EmbeddedResolutionTrait;
    use SortingTrait;

    /**
     * @var EnvironmentNormalizer
     */
    private $normalizer;

    /**
     * @var array
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
     * @return array|null
     */
    public function normalize($input)
    {
        return $this->resource($input);
    }

    /**
     * @param Server $server
     *
     * @return Hyperlink|null
     */
    public function link(Server $server = null): ?Hyperlink
    {
        if (!$server) {
            return null;
        }

        return new Hyperlink(
            ['api.server', ['server' => $server->id()]],
            $server->formatPretty()
        );
    }

    /**
     * @param Server $server
     * @param array $embed
     *
     * @return array|null
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
                ['api.target', ['target' => $deployment->id()]],
                $deployment->formatPretty(true)
            );
        }

        $data = [
            'id' => $server->id(),
            'type' => $server->type(),
            'name' => $server->name()
        ];

        $embedded = $this->resolveEmbedded($properties, array_merge($this->embed, $embed));

        $links = [
            'self' => $this->link($server),
            'environment' => $this->normalizer->link($server->environment()),
            'deployments' => $linkedDeployments
        ];

        return $this->buildResource($data, $embedded, $links);
    }
}
